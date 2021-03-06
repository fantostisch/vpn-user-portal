<?php

use LC\Portal\WireGuard\WGClientConfig;

/* @var LC\Portal\Tpl $this */
$this->layout('base', ['activeItem' => 'WGConfigurations', 'pageTitle' => $this->t('WireGuard Configurations')]);
$this->start('content');

/* @var array<string, WGClientConfig> $wgConfigs
 * @var string|null $wgConfigFileName
 * @var string|null $wgConfigFile
 * @var string|null $newConfigName
 * @var string|null $qrCodeURL
 */ ?>

<?php if (isset($newConfigName)): ?>
    <h2><?= $this->t('Configuration'); /* todo: allow translation to change order */ ?>
        '<?= $this->etr($newConfigName, 25); ?>' <?= $this->t('created'); ?></h2>
    <a download="<?= $this->e($wgConfigFileName); ?>"
       href="data:text/plain;charset=UTF-8;,<?= $wgConfigFile; ?>">
        <?= $this->t('Download config'); ?></a>
    <details>
        <summary><?= $this->e('QR Code'); ?></summary>
        <img alt="WireGuard config QR Code"
             src="<?= 'qr?qr_text='.$wgConfigFile; ?>">
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
    </fieldset>
    <fieldset>
        <button type="submit"><?= $this->t('Create'); ?></button>
    </fieldset>
</form>

<?php if (0 !== count($wgConfigs)): ?>
    <h2><?= $this->t('Existing'); ?></h2>

    <ul class="profileList">
        <?php
        /* @var $wgConfigs array<string, WGClientConfig>
         * @var $wgConfig WGClientConfig
         */
        foreach ($wgConfigs as $publicKey => $wgConfig): ?>
            <li>
                <details>
                    <summary
                            title="<?= $this->e($wgConfig->name); ?>"><?= $this->etr($wgConfig->name, 25); ?></summary>
                    <table class="tbl">
                        <tbody>
                        <tr>
                            <th><?= $this->t('Last modified at'); ?> (<?= $this->e(date('T')); ?>)</th>
                            <td>
                                <?= $this->d($wgConfig->modified); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->t('IP Address'); ?></th>
                            <td>
                                <?= $this->e($wgConfig->ip); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->t('Public key'); ?></th>
                            <td>
                                <?= $this->e($publicKey); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <form class="frm" method="post" action="deleteWGConfig">
                                    <input type="hidden" name="publicKey" value="<?= $this->e($publicKey); ?>">
                                    <button class="warning" type="submit"><?= $this->t('Delete'); ?></button>
                                </form>
                            </th>
                        </tr>
                        </tbody>
                    </table>
                </details>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php $this->stop('content'); ?>
