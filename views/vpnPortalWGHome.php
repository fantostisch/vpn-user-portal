<?php $this->layout('base', ['activeItem' => 'WG', 'pageTitle' => $this->t('WireGuard')]); ?>
<?php $this->start('content'); ?>
<p class="lead"><?= $this->t('Welcome, ') . $this->t($wgUser) . $this->t(', to this WireGuard VPN service!'); ?></p>
<?php foreach (['WGConfigurations' => $this->t('Configurations'),
                   'WGCreateConfiguration' => $this->t('Create configuration')] as $menuKey => $menuText): ?>
    <a href="<?= $this->e($menuKey); ?>"><?= $menuText; ?></a><br>
<?php endforeach; ?>

<?php $this->stop('content'); ?>
