const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const multer = require('multer');
const path = require('path');
const cors = require('cors');
const fs = require('fs');

const app = express();
const port = process.env.PORT || 3000;

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

    // Safely add any new columns to existing database
    const columns = [
        "pass_id TEXT",
        "leader_phone TEXT",
        "leader_dept TEXT",
        "member2_phone TEXT",
        "member2_dept TEXT",
        "track TEXT",
        "payment_ref TEXT",
        "admin_notes TEXT",
        "created_at DATETIME DEFAULT CURRENT_TIMESTAMP"
    ];

    columns.forEach(col => {
        db.run(`ALTER TABLE teams ADD COLUMN ${col}`, () => {});
    });
});

// Configure Multer Storage for Payment Proofs
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
    limits: { fileSize: 10 * 1024 * 1024 } // 10MB limit
});

// Register Team (Strictly 2 members)
app.post('/api/register', upload.single('payment_pic'), (req, res) => {
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
            paymentRef
        } = req.body;

        const paymentPic = req.file ? req.file.filename : null;

        if (!teamName || !password || !college || !leaderName || !leaderEmail || !member2Name || !member2Email) {
            return res.status(400).json({ error: 'Please provide all required fields for both team members.' });
        }

        if (!paymentPic) {
            return res.status(400).json({ error: 'Payment screenshot proof is mandatory.' });
        }

        const stmt = db.prepare(`
            INSERT INTO teams (
                team_name, password, college, track,
                leader_name, leader_email, leader_phone, leader_dept,
                member2_name, member2_email, member2_phone, member2_dept,
                payment_pic, payment_ref, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
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
                leaderDept ? leaderDept.trim() : 'Computer Science',
                member2Name.trim(),
                member2Email.trim().toLowerCase(),
                member2Phone ? member2Phone.trim() : '',
                member2Dept ? member2Dept.trim() : 'Commerce CA',
                paymentPic,
                paymentRef ? paymentRef.trim() : ''
            ],
            function (err) {
                if (err) {
                    if (err.message.includes('UNIQUE constraint failed')) {
                        return res.status(400).json({ error: 'A team with this name already exists! Please choose another team name.' });
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

// Authentication (Admin and Team)
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
            
            // Generate pass_id if not present
            if (!row.pass_id) {
                row.pass_id = `HF26-${1000 + row.id}`;
            }

            res.json({ role: 'user', team: row });
        }
    );
});

// Get Latest Status for a Team
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

// Admin: Approve Team
app.post('/api/admin/approve/:id', (req, res) => {
    const passId = `HF26-${1000 + parseInt(req.params.id, 10)}`;
    db.run(
        `UPDATE teams SET status = 'approved', pass_id = COALESCE(pass_id, ?) WHERE id = ?`,
        [passId, req.params.id],
        function (err) {
            if (err) return res.status(500).json({ error: err.message });
            res.json({ success: true, message: 'Team approved successfully.' });
        }
    );
});

// Admin: Reject Team
app.post('/api/admin/reject/:id', (req, res) => {
    const { note } = req.body || {};
    db.run(
        `UPDATE teams SET status = 'rejected', admin_notes = ? WHERE id = ?`,
        [note || 'Payment details could not be verified.', req.params.id],
        function (err) {
            if (err) return res.status(500).json({ error: err.message });
            res.json({ success: true, message: 'Team marked as rejected.' });
        }
    );
});

// Admin: Delete Team
app.delete('/api/admin/team/:id', (req, res) => {
    db.get(`SELECT payment_pic FROM teams WHERE id = ?`, [req.params.id], (err, row) => {
        if (row && row.payment_pic) {
            const filePath = path.join(uploadDir, row.payment_pic);
            if (fs.existsSync(filePath)) {
                try { fs.unlinkSync(filePath); } catch (e) {}
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
        
        let csv = 'ID,Pass No,Team Name,Status,College,Track,Leader Name,Leader Email,Leader Phone,Leader Dept,Member 2 Name,Member 2 Email,Member 2 Phone,Member 2 Dept,Payment Ref,Created At\n';
        
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
