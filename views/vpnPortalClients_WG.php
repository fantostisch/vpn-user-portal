<?php $this->layout('base', ['activeItem' => 'configs', 'pageTitle' => $this->t('configs')]); ?>
<?php $this->start('content'); ?>

<h2>You have <?= $this->e(count($wgclients)); ?> users </h2>

<?php $count = 0; ?>
<ul class="profileList">
    <?php foreach ($wgclients as $wg): ?>
        <li>
            <details>
                <summary><?= $this->e($wg['name']); ?></summary>
                <table class="tbl">
                    <tbody>
                    <?php ++$count; ?>
                    <tr>
                        <th>#</th>
                        <td>
                            <?= $this->e($count); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Public Key:</th>
                        <td>
                            <?= $this->e($wg['public_key']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>IP:</th>
                        <td>
                            <?= $this->e($wg['ip']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Create at:</th>
                        <td>
                            <?= $this->e($wg['created']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Last Modified:</th>
                        <td>
                            <?= $this->e($wg['modified']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Info:</th>
                        <td>
                            <?= $this->e($wg['info']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Configuration</th>
                        <!-- todo: use https if current page loaded with https -->
                        <td><img src="<?= 'http://'.$this->e($userlink); ?>/<?= $this->e($count); ?>?format=qrcode"
                                 style="width:250px;height:250px;" alt="QR Code with WireGuard configuration">
                            <br>
                            <!-- todo: use https if current page loaded with https -->
                            <a href="<?= 'http://'.$this->e($userlink); ?>/<?= $this->e($count); ?>?format=config"
                               title="<?= $this->e($wg['name']); ?>"><?= $this->etr('Download config', 50); ?></a></td>
                    </tr>
                    </tbody>
                </table>
            </details>
        </li>
    <?php endforeach; ?>
</ul>

<?php $this->stop('content'); ?>
