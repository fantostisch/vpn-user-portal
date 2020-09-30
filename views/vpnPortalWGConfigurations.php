<?php $this->layout('base', ['activeItem' => 'WGConfigurations', 'pageTitle' => $this->t('WireGuard Configurations')]); ?>
<?php $this->start('content'); ?>

<?php if (null !== $wgConfigFile): ?>
    <h2><?= $this->t("Configuration") /* todo: allow translation to change order */ ?>
        '<?= $this->etr($newConfigName, 25); ?>' <?= $this->t('created'); ?></h2>
    <a download="<?= $this->e($wgConfigFileName); ?>"
       href="data:text/plain;charset=utf-16le;base64,<?= $this->e(base64_encode($wgConfigFile)); ?>">
        <?= $this->t('Download config'); ?></a>
    <details>
        <summary><?= $this->e('QR Code'); ?></summary>
        <img alt="WireGuard config QR Code"
             src="data:image/png;base64,<?= $this->e($qrCode); ?>">
    </details>
<?php endif; ?>

<h2><?= $this->t('Create'); ?></h2>
<p>
    <?= $this->t('Manually create and download a WireGuard configuration file for use in your WireGuard client.'); ?>
    <?= $this->t('Choose a name, e.g. "Phone", and a description.'); ?>
</p>

<form method="post" class="frm">
    <fieldset>
        <label for="displayName"><?= $this->t('Name'); ?></label>
        <input type="text" name="displayName" id="displayName" size="32" maxlength="64"
               placeholder="<?= $this->t('Name'); ?>" autofocus required>
        <label for="displayInfo"><?= $this->t('Info'); ?></label>
        <input type="text" name="displayInfo" id="displayInfo" size="32"
               placeholder="<?= $this->t('Info'); ?>" autofocus required>
    </fieldset>
    <fieldset>
        <button type="submit"><?= $this->t('Create'); ?></button>
    </fieldset>
</form>

<?php if (0 !== count($wgConfigs)): ?>
    <h2><?= $this->t('Existing'); ?></h2>

    <ul class="profileList">
        <?php foreach ($wgConfigs as $wgConfig): ?>
            <li>
                <details>
                    <summary
                            title="<?= $this->e($wgConfig['name']); ?>"><?= $this->etr($wgConfig['name'], 25); ?></summary>
                    <table class="tbl">
                        <tbody>
                        <tr>
                            <th><?= $this->t('Info'); ?></th>
                            <td>
                                <?= $this->e($wgConfig['info']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->t('Created at'); /* todo: timezone */ ?></th>
                            <td>
                                <?= $this->d($wgConfig['created']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->t('Last modified at'); /* todo: timezone */ ?></th>
                            <td>
                                <?= $this->d($wgConfig['modified']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->t('IP Address'); ?></th>
                            <td>
                                <?= $this->e($wgConfig['ip']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->t('Public key'); ?></th>
                            <td>
                                <?= $this->e($wgConfig['public_key']); ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </details>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php $this->stop('content'); ?>
