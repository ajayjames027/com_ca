<?php
/**
 * Standalone Pure-PHP SMTP Client for Gmail SSL (Port 465)
 * No Composer or external packages required - works out of the box on BigRock shared hosting.
 *
 * Organizer: Department of Commerce Computer Applications, St. Joseph's College (Autonomous), Tiruchirappalli
 * Developed by: FrontierWox Tech Private Limited
 */

require_once __DIR__ . '/../config.php';

class SimpleSmtpMailer {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $fromName;
    private $socket;
    private $debug = false;

    public function __construct($host = EMAIL_HOST, $port = EMAIL_PORT, $user = EMAIL_USER, $pass = EMAIL_PASS, $fromName = EMAIL_FROM_NAME) {
        $this->host = $host;
        $this->port = (int)$port;
        $this->user = trim($user);
        $this->pass = str_replace(' ', '', trim($pass)); // Normalize Gmail App Password (removes spaces)
        $this->fromName = $fromName;
    }

    private function getResponse() {
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) === " ") {
                break;
            }
        }
        return $response;
    }

    private function sendCommand($cmd, $expectedCode) {
        fputs($this->socket, $cmd . "\r\n");
        $response = $this->getResponse();
        $code = (int)substr($response, 0, 3);
        if ($code !== (int)$expectedCode) {
            throw new Exception("SMTP Error: Command '$cmd' failed with response: $response");
        }
        return $response;
    }

    public function send($toEmails, $subject, $htmlBody) {
        if (!is_array($toEmails)) {
            $toEmails = array_map('trim', explode(',', $toEmails));
        }
        $toEmails = array_filter($toEmails, function($e) {
            return filter_var($e, FILTER_VALIDATE_EMAIL);
        });

        if (empty($toEmails)) {
            return ['sent' => false, 'error' => 'No valid recipient email addresses provided.'];
        }

        if (empty($this->pass)) {
            return ['sent' => false, 'error' => 'EMAIL_PASS not configured in config.php.'];
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $remoteSocket = "ssl://" . $this->host . ":" . $this->port;
        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client($remoteSocket, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);

        if (!$this->socket) {
            // Fallback to PHP mail() if direct socket blocked by host
            return $this->fallbackPhpMail($toEmails, $subject, $htmlBody, "Could not open socket ($errstr)");
        }

        stream_set_timeout($this->socket, 15);

        try {
            $greeting = $this->getResponse();
            if (substr($greeting, 0, 3) !== '220') {
                throw new Exception("Invalid greeting: $greeting");
            }

            $this->sendCommand("EHLO localhost", 250);
            $this->sendCommand("AUTH LOGIN", 334);
            $this->sendCommand(base64_encode($this->user), 334);
            $this->sendCommand(base64_encode($this->pass), 235);
            $this->sendCommand("MAIL FROM:<" . $this->user . ">", 250);

            foreach ($toEmails as $to) {
                $this->sendCommand("RCPT TO:<" . $to . ">", 250);
            }

            $this->sendCommand("DATA", 354);

            $boundary = "----=_Part_" . md5(uniqid(time()));
            $headers = [];
            $headers[] = "Date: " . date('r');
            $headers[] = "From: =?UTF-8?B?" . base64_encode($this->fromName) . "?= <" . $this->user . ">";
            $headers[] = "To: " . implode(', ', $toEmails);
            $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: base64";
            $headers[] = "X-Mailer: FrontierWox-PHP-SMTP";

            $rawEmail = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.";
            
            fputs($this->socket, $rawEmail . "\r\n");
            $dataResp = $this->getResponse();
            if (substr($dataResp, 0, 3) !== '250') {
                throw new Exception("DATA rejected: $dataResp");
            }

            $this->sendCommand("QUIT", 221);
            fclose($this->socket);

            return ['sent' => true, 'message' => 'Email sent successfully via Gmail SMTP'];
        } catch (Exception $e) {
            if ($this->socket) {
                @fclose($this->socket);
            }
            return $this->fallbackPhpMail($toEmails, $subject, $htmlBody, $e->getMessage());
        }
    }

    private function fallbackPhpMail($toEmails, $subject, $htmlBody, $smtpErrorReason) {
        $to = implode(', ', $toEmails);
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->user . '>',
            'Reply-To: ' . $this->user,
            'X-Mailer: PHP/' . phpversion()
        ];
        $sent = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
        if ($sent) {
            return ['sent' => true, 'note' => 'Dispatched via fallback mail(). SMTP error: ' . $smtpErrorReason];
        }
        return ['sent' => false, 'error' => $smtpErrorReason];
    }
}

/**
 * Builds and sends the official HACKFEST '26 Gate Pass Verification Email
 */
function sendVerificationEmailPHP($team) {
    $recipients = [];
    if (!empty($team['leader_email'])) $recipients[] = trim($team['leader_email']);
    if (!empty($team['member2_email'])) $recipients[] = trim($team['member2_email']);
    $recipients = array_unique($recipients);

    if (empty($recipients)) {
        return ['sent' => false, 'error' => 'No recipient email addresses found for team.'];
    }

    $passId = !empty($team['pass_id']) ? $team['pass_id'] : ('HF26-' . (1000 + (int)$team['id']));
    $whatsappLink = WHATSAPP_LINK;
    
    // Determine login portal URL
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'hackfest.frontierwox.in';
    $portalUrl = $protocol . '://' . $host . '/login.html';

    $subject = "🎉 Verification Approved: Team \"{$team['team_name']}\" - HACKFEST '26 Gate Pass Issued";

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
</head>
<body style="margin: 0; padding: 20px; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <!-- Header -->
        <div style="background: #0f172a; padding: 26px 20px; text-align: center; color: #ffffff;">
            <h2 style="margin: 0; font-size: 19px; letter-spacing: 1.2px; font-weight: 700; color: #ffffff;">ST. JOSEPH'S COLLEGE (AUTONOMOUS)</h2>
            <p style="margin: 5px 0 0; font-size: 13px; color: #f59e0b; font-weight: 600; text-transform: uppercase;">DEPARTMENT OF COMMERCE COMPUTER APPLICATIONS</p>
            <div style="margin: 16px 0 0; padding-top: 14px; border-top: 1px solid rgba(255, 255, 255, 0.12);">
                <h1 style="margin: 0; font-size: 26px; color: #38bdf8; font-weight: 800; letter-spacing: 0.5px;">HACKFEST '26</h1>
                <p style="margin: 4px 0 0; font-size: 13px; color: #cbd5e1;">Official Registration Verification &amp; Campus Gate Pass</p>
            </div>
        </div>

        <!-- Body -->
        <div style="padding: 28px 24px;">
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;">
                <span style="font-size: 15px; font-weight: 700; color: #047857;">
                    ✓ Registration &amp; Payment Successfully Verified!
                </span>
            </div>

            <p style="font-size: 15px; line-height: 1.6; color: #1e293b; margin: 0 0 12px;">
                Dear <strong>{$team['leader_name']}</strong> and <strong>{$team['member2_name']}</strong>,
            </p>
            <p style="font-size: 14px; line-height: 1.6; color: #334155; margin: 0 0 20px;">
                Congratulations! Your team <strong>"{$team['team_name']}"</strong> representing <strong>{$team['college']}</strong> has been officially approved for <strong>HACKFEST '26</strong> by the Department of Commerce Computer Applications, St. Joseph's College (Autonomous), Tiruchirappalli.
            </p>

            <!-- Gate Pass Box -->
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 18px; margin: 20px 0;">
                <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.8px;">Official Campus Gate Pass ID</div>
                <div style="font-size: 24px; font-weight: 800; color: #1d4ed8; margin: 4px 0 8px;">{$passId}</div>
                <div style="font-size: 13px; color: #475569; padding-top: 6px; border-top: 1px dashed #cbd5e1;">
                    <strong>Assigned Track:</strong> {$team['track']}
                </div>
            </div>

            <!-- WhatsApp Community Button -->
            <div style="margin: 24px 0 16px; text-align: center;">
                <a href="{$whatsappLink}" target="_blank" style="display: inline-block; background: #25d366; color: #ffffff; padding: 13px 28px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 15px; box-shadow: 0 4px 6px rgba(37, 211, 102, 0.25);">
                    💬 Join Official WhatsApp Group &rarr;
                </a>
            </div>

            <!-- Dashboard Button -->
            <div style="margin: 0 0 24px; text-align: center;">
                <a href="{$portalUrl}" target="_blank" style="display: inline-block; background: #1d4ed8; color: #ffffff; padding: 11px 22px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px;">
                    🖨️ Login to Download &amp; Print Gate Pass
                </a>
            </div>

            <!-- Mandatory Rules Notice -->
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 16px; margin: 20px 0; font-size: 13px; color: #92400e; line-height: 1.5;">
                <strong>⚠️ Mandatory Campus Entry Requirements:</strong><br>
                1. Both registered participants must carry a printed copy of the Gate Pass.<br>
                2. Both participants must present their original College Identity Card at the main gate for security clearance.
            </div>

            <!-- Coordinators -->
            <div style="border-top: 1px solid #e2e8f0; padding-top: 18px; margin-top: 24px; font-size: 13px; color: #64748b; line-height: 1.6;">
                <strong style="color: #1e293b;">Need Assistance? Contact Event Coordinators:</strong><br>
                &bull; Staff Coordinator: <strong>Dr. B. Fatimamary</strong> &ndash; 96294 82064<br>
                &bull; Student Coordinator: <strong>Mohamed Ismail K S</strong> &ndash; 63818 98996<br>
                &bull; Student Coordinator: <strong>K Abinesh</strong> &ndash; 96269 83425
            </div>
        </div>

        <!-- Footer -->
        <div style="background: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; line-height: 1.5;">
            <strong>St. Joseph's College (Autonomous)</strong> &bull; Tiruchirappalli - 620 002<br>
            Organized by the Department of Commerce Computer Applications<br>
            <span style="font-size: 11px; color: #94a3b8;">Technical Partner: FrontierWox Tech Private Limited</span>
        </div>
    </div>
</body>
</html>
HTML;

    $mailer = new SimpleSmtpMailer();
    return $mailer->send($recipients, $subject, $html);
}
