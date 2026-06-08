<?php $view->extend('layout'); ?>

<?php $this->section('content'); ?>

<div class="alert alert-danger">
    OPcache is not enabled. Please enable it in your php.ini file.
</div>

<?php $this->endSection(); ?>
