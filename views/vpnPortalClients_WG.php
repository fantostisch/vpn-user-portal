<?php $this->layout('base', ['activeItem' => 'configs', 'pageTitle' => $this->t('configs')]); ?>
<?php $this->start('content'); ?>

<h2>You have <?=$this->e(count($wgclients))?> users </h2>

<?php $count = 0?>
<ul class="profileList">
<?php foreach ($wgclients as $wg): ?>
    <li><details>
        <summary><?=$this->e($wg['name']); ?></summary>
    <table class="tbl">
    <tbody>
    <?php $count++?>
        <tr>
        <th># </th>
        <td><li><?=$this->e($count)?></li></td></tr>
        <tr><th>Public Key: </th>
        <td><li><?=$this->e($wg['public_key']) ?></li></td></tr>
        <tr><th>IP: </th>
        <td><li><?=$this->e($wg['ip']) ?></li></td></tr>
       <tr> <th>Create at: </th>
        <td><li><?=$this->e($wg['created']) ?></li></td></tr>
     <tr>   <th>Last Modified: </th>
        <td><li><?=$this->e($wg['modified']) ?></li></td></tr>
        <tr><th>Info: </th>
        <td><li><?=$this->e($wg['info']) ?></li></td></tr>
        <tr><th>QR Code</th>
            <!-- todo: use https if current page loaded with https -->
        <td><img src="<?="http://" . $this->e($userlink); ?>/<?=$this->e($count); ?>?format=qrcode" style="width:250px;height:250px;"></td></tr>
        <td><a href="<?="http://" . $this->e($userlink); ?>/<?=$this->e($count); ?>?format=config" title="<?=$this->e($wg['name']); ?>"><?=$this->etr('Download config', 50); ?></a></td>

    </tbody>
    </table>
    </details>
    </li>
<?php endforeach; ?>
</ul>


<?php $this->stop('content'); ?>
