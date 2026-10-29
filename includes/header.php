<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo isset($pageTitle) && $pageTitle !== '' ? htmlspecialchars($pageTitle) . ' — Rosa Indah Printing' : 'Rosa Indah Printing'; ?></title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <!-- AOS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">

    <!-- CSS Website -->
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>?v=<?php echo @filemtime(__DIR__ . '/../assets/css/style.css') ?: '20261032'; ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/responsive.css'); ?>?v=<?php echo @filemtime(__DIR__ . '/../assets/css/responsive.css') ?: '20261032'; ?>">

</head>

<body>