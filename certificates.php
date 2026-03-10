<?php
// api/certificates.php  — Admin only
// GET  ?action=list     [search, delivery_status]
// GET  ?action=get      id
// POST ?action=generate {student_id,course_id,cert_type,grade,issue_date,
//                        organisation,org_unit,country,state,city,director_name,custom_message}
// POST ?action=send     {id}
// POST ?action=delete   {id}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
cors();

$admin = needAdmin();
$act   = $_GET['action'] ?? '';
$b     = body();
$pdo   = db();

/* LIST ───────────────────────────────────────────────────── */
if ($act === 'list') {
    $w = ['1=1']; $p = [];
    if (!empty($b['delivery_status'])) { $w[] = 'cert.delivery_status=?'; $p[] = $b['delivery_status']; }
    if (!empty($b['search'])) {
        $like = '%'.$b['search'].'%';
        $w[]  = '(CONCAT(s.first_name," ",s.last_name) LIKE ? OR c.name LIKE ? OR cert.id LIKE ?)';
        array_push($p, $like, $like, $like);
    }
    $st = $pdo->prepare(
        'SELECT cert.id, cert.cert_type, cert.grade, cert.issue_date,
                cert.delivery_status, cert.sent_at, cert.created_at,
                CONCAT("CERT-",LPAD(cert.id,6,"0")) AS cert_code,
                CONCAT(s.first_name," ",s.last_name) AS student_name,
                s.email AS student_email,
                c.name AS course_name
         FROM certificates cert
         JOIN students s ON cert.student_id=s.id
         JOIN courses  c ON cert.course_id=c.id
         WHERE '.implode(' AND ',$w).' ORDER BY cert.created_at DESC'
    );
    $st->execute($p);
    ok($st->fetchAll());
}

/* GET ONE ───────────────────────────────────────────────── */
elseif ($act === 'get') {
    $id = (int)($b['id'] ?? 0); if (!$id) fail('ID required.');
    $st = $pdo->prepare(
        'SELECT cert.*, CONCAT("CERT-",LPAD(cert.id,6,"0")) AS cert_code,
                CONCAT(s.first_name," ",s.last_name) AS student_name,
                s.email AS student_email, c.name AS course_name,
                u.name AS issued_by_name
         FROM certificates cert
         JOIN students s ON cert.student_id=s.id
         JOIN courses  c ON cert.course_id=c.id
         LEFT JOIN users u ON cert.issued_by=u.id
         WHERE cert.id=?'
    );
    $st->execute([$id]);
    $row = $st->fetch(); if (!$row) fail('Not found.', 404);
    ok($row);
}

/* GENERATE ──────────────────────────────────────────────── */
elseif ($act === 'generate') {
    foreach (['student_id','course_id','issue_date'] as $f)
        if (empty($b[$f])) fail("$f required.");

    $sid = (int)$b['student_id'];
    $cid = (int)$b['course_id'];

    // Validate student & course exist
    $sc = $pdo->prepare('SELECT id FROM students WHERE id=?'); $sc->execute([$sid]);
    if (!$sc->fetch()) fail('Student not found.');
    $cc = $pdo->prepare('SELECT id FROM courses WHERE id=?');  $cc->execute([$cid]);
    if (!$cc->fetch()) fail('Course not found.');

    // Prevent duplicate
    $dup = $pdo->prepare('SELECT id FROM certificates WHERE student_id=? AND course_id=? LIMIT 1');
    $dup->execute([$sid, $cid]);
    if ($dup->fetch()) fail('Certificate already issued for this student & course.');

    $pdo->prepare(
        'INSERT INTO certificates(student_id,course_id,cert_type,grade,issue_date,
                                  organisation,org_unit,country,state,city,
                                  director_name,custom_message,issued_by)
         VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([
        $sid, $cid,
        clean($b['cert_type']      ?? 'Certificate of Completion'),
        clean($b['grade']          ?? 'Pass'),
        $b['issue_date'],
        clean($b['organisation']   ?? 'NextGen Technologies'),
        clean($b['org_unit']       ?? ''),
        clean($b['country']        ?? 'IN'),
        clean($b['state']          ?? ''),
        clean($b['city']           ?? ''),
        clean($b['director_name']  ?? ''),
        clean($b['custom_message'] ?? ''),
        $admin['id'],
    ]);
    $newId = $pdo->lastInsertId();

    // Mark enrollment & student completed
    $pdo->prepare('UPDATE enrollments SET status="Completed",progress=100 WHERE student_id=? AND course_id=?')->execute([$sid,$cid]);
    $pdo->prepare('UPDATE students SET status="Completed" WHERE id=? AND status="Approved"')->execute([$sid]);

    logAct($admin['id'], 'CERT_GENERATED', "cert:$newId student:$sid");
    ok(['id' => $newId, 'cert_code' => 'CERT-'.str_pad($newId,6,'0',STR_PAD_LEFT)], 'Certificate generated');
}

/* SEND ──────────────────────────────────────────────────── */
elseif ($act === 'send') {
    $id = (int)($b['id'] ?? 0); if (!$id) fail('ID required.');
    $st = $pdo->prepare('SELECT delivery_status FROM certificates WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch(); if (!$row) fail('Not found.');
    if ($row['delivery_status'] === 'Sent') fail('Already sent.');

    // TODO: integrate PHPMailer/SMTP here to email the certificate PDF

    $pdo->prepare('UPDATE certificates SET delivery_status="Sent",sent_at=NOW() WHERE id=?')->execute([$id]);
    logAct($admin['id'], 'CERT_SENT', "cert:$id");
    ok([], 'Marked as sent');
}

/* DELETE ─────────────────────────────────────────────────── */
elseif ($act === 'delete') {
    $id = (int)($b['id'] ?? 0); if (!$id) fail('ID required.');
    $pdo->prepare('DELETE FROM certificates WHERE id=?')->execute([$id]);
    logAct($admin['id'], 'CERT_DELETED', "cert:$id");
    ok([], 'Deleted');
}

else fail('Unknown action.', 404);
