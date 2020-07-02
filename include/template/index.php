<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>

    <title>OpenVPN-Admin</title>

    <link rel="stylesheet" href="vendor/bootstrap/dist/css/bootstrap.min.css" type="text/css"/>
    <link rel="stylesheet" href="vendor/x-editable/dist/bootstrap3-editable/css/bootstrap-editable.css"
          type="text/css"/>
    <link rel="stylesheet" href="vendor/bootstrap-table/dist/bootstrap-table.min.css" type="text/css"/>
    <link rel="stylesheet" href="vendor/bootstrap-datepicker/dist/css/bootstrap-datepicker3.min.css" type="text/css"/>
    <link rel="stylesheet"
          href="vendor/bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.min.css"
          type="text/css"/>
    <link rel="stylesheet"
          href="vendor/bootstrap-table/dist/extensions/sticky-header/bootstrap-table-sticky-header.min.css"
          type="text/css"/>
    <link rel="stylesheet" href="css/index.css" type="text/css"/>

    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body class='container-fluid'>
<?php if (!empty($data['error'])): ?>
    <div class="alert alert-danger" role="alert"><?php echo $data['error']; ?></div>
<?php endif; ?>
<?php if (!empty($data['success'])): ?>
    <div class="alert alert-success" role="alert"><?php echo $data['success']; ?></div>
<?php endif; ?>
<?php echo (!empty($data['content'])) ? $data['content'] : ''; ?>
<div id="message-stage">
    <!-- used to display application messages (failures / status-notes) to the user -->
</div>
</body>
</html>