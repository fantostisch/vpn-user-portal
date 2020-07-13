<?php $this->layout('base', ['activeItem' => 'WG', 'pageTitle' => $this->t('WireGuard')]); ?>
<?php $this->start('content'); ?>

<p class="lead"><?=$this->t('Welcome, ').$this->t($wguser).$this->t(', to this WireGuard VPN service!'); ?></p>
<tr>
    <td><span title="name"<?=$this->t($clien);?> </td>
</tr>
   <?php foreach (['CR' => $this->t('Create Clients'), 'configs' => $this->t('Configurations')] as $menuKey => $menuText): ?>
<?php if ($menuKey === $activeItem): ?>
    <li class="active">
        <li>
    <?php endif; ?>
    <a href="<?=$this->e($menuKey); ?>"><?=$menuText; ?></a>

    </li>
<?php endforeach; ?>

<?php $this->stop('content'); ?>