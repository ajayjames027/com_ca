require('dotenv').config();
const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const multer = require('multer');
const path = require('path');
const cors = require('cors');
const fs = require('fs');
const nodemailer = require('nodemailer');

const app = express();
const port = process.env.PORT || 3000;

// Email Transporter Configuration (ajayjames027@gmail.com)
const EMAIL_USER = process.env.EMAIL_USER || 'ajayjames027@gmail.com';
const EMAIL_PASS = (process.env.EMAIL_PASS || '').replace(/\s+/g, ''); // Normalizes Gmail App Password

let transporter = null;
if (EMAIL_PASS) {
    transporter = nodemailer.createTransport({
        service: 'gmail',
        auth: {
            user: EMAIL_USER,
            pass: EMAIL_PASS
        }
    });
    console.log(`Live email notifications active via Gmail SMTP (${EMAIL_USER})`);
} else {
    // Development fallback (logs to console without failing)
    console.log('NOTE: EMAIL_PASS not set. Emails will be logged to console. To enable live sending from ajayjames027@gmail.com, set EMAIL_PASS with your Gmail App Password.');
}

async function sendVerificationEmail(team) {
    const recipients = [team.leader_email, team.member2_email].filter(Boolean).join(', ');
    const passId = team.pass_id || `HF26-${1000 + team.id}`;
    const whatsappLink = 'https://chat.whatsapp.com/CGQ2aJinJQNIbKR39taZc0';
    const portalUrl = process.env.APP_URL || 'http://localhost:3000/login.html';

    const mailOptions = {
        from: `"HACKFEST '26 - St. Joseph's College" <${EMAIL_USER}>`,
        to: recipients,
        subject: `🎉 Verification Approved: Team "${team.team_name}" - HACKFEST '26 Gate Pass Issued`,
        html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; color: #1e293b;">
            <div style="background: #0f172a; padding: 24px; text-align: center; color: #ffffff;">
                <h2 style="margin: 0; font-size: 20px; letter-spacing: 1px;">ST. JOSEPH'S COLLEGE (AUTONOMOUS)</h2>
                <p style="margin: 4px 0 0; font-size: 13px; color: #fbbf24;">DEPARTMENT OF COMMERCE COMPUTER APPLICATIONS</p>
                <h1 style="margin: 14px 0 0; font-size: 26px; color: #38bdf8;">HACKFEST '26</h1>
                <p style="margin: 4px 0 0; font-size: 13px; color: #cbd5e1;">Official Verification &amp; Campus Gate Pass</p>
            </div>

            <div style="padding: 24px;">
                <p style="font-size: 16px; font-weight: bold; color: #15803d; margin-top: 0;">
                    ✓ Registration &amp; Payment Successfully Verified!
                </p>
                <p style="font-size: 15px; line-height: 1.6;">
                    Dear <strong>${team.leader_name}</strong> and <strong>${team.member2_name}</strong>,
                </p>
                <p style="font-size: 14px; line-height: 1.6; color: #334155;">
                    Congratulations! Your team <strong>"${team.team_name}"</strong> from <strong>${team.college}</strong> has been officially approved for <strong>HACKFEST '26</strong> by the Department of Commerce Computer Applications, St. Joseph's College (Autonomous), Tiruchirappalli.
                </p>

                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin: 20px 0;">
                    <div style="font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: bold;">Official Gate Pass ID</div>
                    <div style="font-size: 22px; font-weight: bold; color: #1d4ed8; margin: 4px 0;">${passId}</div>
                    <div style="font-size: 13px; color: #475569;"><strong>Assigned Track:</strong> ${team.track || 'General Application Development'}</div>
                </div>

                <div style="margin: 24px 0; text-align: center;">
                    <a href="${whatsappLink}" target="_blank" style="display: inline-block; background: #25d366; color: #ffffff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 15px; margin-right: 10px;">
                        💬 Join Official WhatsApp Group →
                    </a>
                </div>

                <div style="margin: 15px 0; text-align: center;">
                    <a href="${portalUrl}" target="_blank" style="display: inline-block; background: #1d4ed8; color: #ffffff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px;">
                        🖨️ Download / Print Campus Gate Pass
                    </a>
                </div>

                <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 14px; margin: 20px 0; font-size: 13px; color: #92400e;">
                    <strong>⚠️ Important Entry Requirement:</strong><br>
                    Both team members must present their printed Gate Pass along with their original College ID Card at the college security gate for campus entry.
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 24px; font-size: 13px; color: #64748b;">
                    <strong>Need Help? Contact Coordinators:</strong><br>
                    • Staff Coordinator: Dr. B. Fatimamary (96294 82064)<br>
                    • Student Coordinator: Mohamed Ismail K S (63818 98996)<br>
                    • Student Coordinator: K Abinesh (96269 83425)
                </div>
            </div>

            <div style="background: #f1f5f9; padding: 14px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
                St. Joseph's College (Autonomous), Tiruchirappalli - 620 002<br>
                Organized by the Department of Commerce Computer Applications • Developed by FrontierWox Tech Pvt Ltd
            </div>
        </div>
        `
    };

    if (transporter) {
        try {
            const info = await transporter.sendMail(mailOptions);
            console.log(`Live verification email sent from ${EMAIL_USER} to ${recipients}: ${info.messageId}`);
            return { sent: true, messageId: info.messageId };
        } catch (err) {
            console.error('Failed to send live email:', err.message);
            return { sent: false, error: err.message };
        }
    } else {
        console.log(`[SIMULATED EMAIL NOTIFICATION from ${EMAIL_USER}] to [${recipients}]`);
        console.log(`Subject: ${mailOptions.subject}`);
        console.log(`Team: ${team.team_name} | Pass ID: ${passId}`);
        console.log(`WhatsApp Link: ${whatsappLink}`);
        return { sent: false, note: 'EMAIL_PASS not configured in environment' };
    }
}

// Ensure upload directory exists
const uploadDir = path.join(__dirname, 'uploads');
if (!fs.existsSync(uploadDir)){
    fs.mkdirSync(uploadDir, { recursive: true });
}

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static(__dirname));
app.use('/uploads', express.static(uploadDir));

// SQLite Database Setup
const db = new sqlite3.Database('hackfest.db', (err) => {
    if (err) console.error('Database connection error:', err.message);
    else console.log('Connected to SQLite hackfest.db');
});

// Create / Migrate Schema
db.serialize(() => {
    db.run(`CREATE TABLE IF NOT EXISTS teams (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pass_id TEXT,
        team_name TEXT UNIQUE,
        password TEXT,
        college TEXT,
        leader_name TEXT,
        leader_email TEXT,
        leader_phone TEXT,
        leader_dept TEXT,
        member2_name TEXT,
        member2_email TEXT,
        member2_phone TEXT,
        member2_dept TEXT,
        track TEXT,
        payment_pic TEXT,
        payment_ref TEXT,
        status TEXT DEFAULT 'pending',
        admin_notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    const columns = [
        "pass_id TEXT",
        "leader_phone TEXT",
        "leader_dept TEXT",
        "member2_phone TEXT",
        "member2_dept TEXT",
        "track TEXT",
        "payment_ref TEXT",
        "payment_pic2 TEXT",
        "payment_ref2 TEXT",
        "admin_notes TEXT",
        "created_at DATETIME DEFAULT CURRENT_TIMESTAMP"
    ];

    columns.forEach(col => {
        db.run(`ALTER TABLE teams ADD COLUMN ${col}`, () => {});
    });
});

// Multer Storage Configuration
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, uploadDir);
    },
    filename: (req, file, cb) => {
        const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
        cb(null, 'payment-' + uniqueSuffix + path.extname(file.originalname));
    }
});
const upload = multer({
    storage: storage,
    limits: { fileSize: 10 * 1024 * 1024 }
});

const uploadFields = upload.fields([
    { name: 'payment_pic', maxCount: 1 },
    { name: 'payment_pic2', maxCount: 1 }
]);

// Register Team (Strictly 2 members)
app.post('/api/register', uploadFields, (req, res) => {
    try {
        const {
            teamName,
            password,
            college,
            track,
            leaderName,
            leaderEmail,
            leaderPhone,
            leaderDept,
            member2Name,
            member2Email,
            member2Phone,
            member2Dept,
            paymentRef,
            paymentRef2
        } = req.body;

        const files = req.files || {};
        const paymentPic = (files['payment_pic'] && files['payment_pic'][0]) ? files['payment_pic'][0].filename : null;
        const paymentPic2 = (files['payment_pic2'] && files['payment_pic2'][0]) ? files['payment_pic2'][0].filename : paymentPic;

        if (!teamName || !password || !college || !leaderName || !leaderEmail || !member2Name || !member2Email) {
            return res.status(400).json({ error: 'Please provide all required fields for both team members.' });
        }

        if (!paymentPic) {
            return res.status(400).json({ error: 'Payment receipt screenshot is required for Participant 1.' });
        }

        const stmt = db.prepare(`
            INSERT INTO teams (
                team_name, password, college, track,
                leader_name, leader_email, leader_phone, leader_dept,
                member2_name, member2_email, member2_phone, member2_dept,
                payment_pic, payment_ref, payment_pic2, payment_ref2, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        `);

        stmt.run(
            [
                teamName.trim(),
                password,
                college.trim(),
                track || 'General Application Development',
                leaderName.trim(),
                leaderEmail.trim().toLowerCase(),
                leaderPhone ? leaderPhone.trim() : '',
                leaderDept ? leaderDept.trim() : 'Commerce Computer Applications',
                member2Name.trim(),
                member2Email.trim().toLowerCase(),
                member2Phone ? member2Phone.trim() : '',
                member2Dept ? member2Dept.trim() : 'Commerce Computer Applications',
                paymentPic,
                paymentRef ? paymentRef.trim() : '',
                paymentPic2,
                paymentRef2 ? paymentRef2.trim() : (paymentRef ? paymentRef.trim() : '')
            ],
            function (err) {
                if (err) {
                    if (err.message.includes('UNIQUE constraint failed')) {
                        return res.status(400).json({ error: 'A team with this name already exists! Please choose another name.' });
                    }
                    return res.status(400).json({ error: err.message });
                }

                const insertedId = this.lastID;
                const passId = `HF26-${1000 + insertedId}`;

                db.run(`UPDATE teams SET pass_id = ? WHERE id = ?`, [passId, insertedId], () => {
                    res.json({
                        success: true,
                        message: 'Team registered successfully! Please wait for committee approval.',
                        teamId: insertedId,
                        passId: passId
                    });
                });
            }
        );
        stmt.finalize();
    } catch (e) {
        console.error(e);
        res.status(500).json({ error: 'Internal server error during registration.' });
    }
});

// Authentication
app.post('/api/login', (req, res) => {
    const { username, password } = req.body;
    if (!username || !password) {
        return res.status(400).json({ error: 'Username and password are required' });
    }

    const adminUser = process.env.ADMIN_USER || 'admin';
    const adminPass = process.env.ADMIN_PASS || 'admin123';

    if (username.trim() === adminUser && password === adminPass) {
        return res.json({ role: 'admin', token: 'admin-session-active' });
    }

    db.get(
        `SELECT * FROM teams WHERE LOWER(team_name) = LOWER(?) AND password = ?`,
        [username.trim(), password],
        (err, row) => {
            if (err) return res.status(500).json({ error: 'Database error' });
            if (!row) return res.status(401).json({ error: 'Invalid team name or password.' });
            
            if (!row.pass_id) {
                row.pass_id = `HF26-${1000 + row.id}`;
            }

            res.json({ role: 'user', team: row });
        }
    );
});

// Latest Status for a Team
app.get('/api/team/status/:teamName', (req, res) => {
    db.get(
        `SELECT * FROM teams WHERE LOWER(team_name) = LOWER(?)`,
        [req.params.teamName.trim()],
        (err, row) => {
            if (err || !row) return res.status(404).json({ error: 'Team not found' });
            if (!row.pass_id) row.pass_id = `HF26-${1000 + row.id}`;
            res.json({ team: row });
        }
    );
});

// Admin: Get All Teams
app.get('/api/admin/teams', (req, res) => {
    db.all(`SELECT * FROM teams ORDER BY id DESC`, (err, rows) => {
        if (err) return res.status(500).json({ error: err.message });
        rows.forEach(r => {
            if (!r.pass_id) r.pass_id = `HF26-${1000 + r.id}`;
        });
        res.json(rows);
    });
});

// Admin: Approve Team & Trigger Automated Email
app.post('/api/admin/approve/:id', (req, res) => {
    const teamId = req.params.id;
    const passId = `HF26-${1000 + parseInt(teamId, 10)}`;

    db.get(`SELECT * FROM teams WHERE id = ?`, [teamId], (err, team) => {
        if (err || !team) return res.status(404).json({ error: 'Team not found' });

        db.run(
            `UPDATE teams SET status = 'approved', pass_id = COALESCE(pass_id, ?) WHERE id = ?`,
            [passId, teamId],
            async function (err2) {
                if (err2) return res.status(500).json({ error: err2.message });

                team.status = 'approved';
                team.pass_id = passId;

                // Send automated email notification to both members from ajayjames027@gmail.com
                const emailResult = await sendVerificationEmail(team);

                res.json({
                    success: true,
                    message: 'Team approved successfully and Gate Pass issued.',
                    email: emailResult
                });
            }
        );
    });
});

// Admin: Reject Team
app.post('/api/admin/reject/:id', (req, res) => {
    const { note } = req.body || {};
    db.run(
        `UPDATE teams SET status = 'rejected', admin_notes = ? WHERE id = ?`,
        [note || 'Payment details could not be verified on the college portal.', req.params.id],
        function (err) {
            if (err) return res.status(500).json({ error: err.message });
            res.json({ success: true, message: 'Team marked as rejected.' });
        }
    );
});

// Admin: Delete Team
app.delete('/api/admin/team/:id', (req, res) => {
    db.get(`SELECT payment_pic, payment_pic2 FROM teams WHERE id = ?`, [req.params.id], (err, row) => {
        if (row) {
            if (row.payment_pic) {
                const filePath = path.join(uploadDir, row.payment_pic);
                if (fs.existsSync(filePath)) {
                    try { fs.unlinkSync(filePath); } catch (e) {}
                }
            }
            if (row.payment_pic2 && row.payment_pic2 !== row.payment_pic) {
                const filePath2 = path.join(uploadDir, row.payment_pic2);
                if (fs.existsSync(filePath2)) {
                    try { fs.unlinkSync(filePath2); } catch (e) {}
                }
            }
        }
        db.run(`DELETE FROM teams WHERE id = ?`, [req.params.id], function (err) {
            if (err) return res.status(500).json({ error: err.message });
            res.json({ success: true, message: 'Team deleted successfully.' });
        });
    });
});

// Admin: Export CSV
app.get('/api/admin/export-csv', (req, res) => {
    db.all(`SELECT * FROM teams ORDER BY id ASC`, (err, rows) => {
        if (err) return res.status(500).send('Error generating CSV');
        
        let csv = 'ID,Pass No,Team Name,Status,College,Track,Leader Name,Leader Email,Leader Phone,Leader Dept,Member 2 Name,Member 2 Email,Member 2 Phone,Member 2 Dept,M1 Payment Ref (Rs 300),M2 Payment Ref (Rs 300),Created At\n';
        
        rows.forEach(r => {
            const pass = r.pass_id || `HF26-${1000 + r.id}`;
            const clean = (val) => `"${(val || '').toString().replace(/"/g, '""')}"`;
            csv += [
                r.id,
                clean(pass),
                clean(r.team_name),
                clean(r.status),
                clean(r.college),
                clean(r.track),
                clean(r.leader_name),
                clean(r.leader_email),
                clean(r.leader_phone),
                clean(r.leader_dept),
                clean(r.member2_name),
                clean(r.member2_email),
                clean(r.member2_phone),
                clean(r.member2_dept),
                clean(r.payment_ref),
                clean(r.payment_ref2 || r.payment_ref),
                clean(r.created_at)
            ].join(',') + '\n';
        });

        res.setHeader('Content-Type', 'text/csv');
        res.setHeader('Content-Disposition', 'attachment; filename="hackfest26_teams.csv"');
        res.status(200).send(csv);
    });
});

app.listen(port, () => {
    console.log(`Server running at http://localhost:${port}`);
});
