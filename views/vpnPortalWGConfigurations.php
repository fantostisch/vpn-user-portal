<?php

use LC\Portal\Federation\WGClientConfig;

/* @var LC\Portal\Tpl $this */
$this->layout('base', ['activeItem' => 'WGConfigurations', 'pageTitle' => $this->t('WireGuard Configurations')]);
$this->start('content');

/* @var array<string, WGClientConfig> $wgConfigs
 * @var string|null $wgConfigFileName
 * @var string|null $wgConfig
 * @var string|null $newConfigName
 * @var string|null $qrCodeURL
 */ ?>

<? if (null !== $wgConfig): ?>
    <h2><?= $this->t("Configuration") /* todo: allow translation to change order */ ?>
        '<?= $this->etr($newConfigName, 25); ?>' <?= $this->t('created'); ?></h2>
    <a download="<?= $this->e($wgConfigFileName); ?>"
       href="data:text/plain;charset=utf-16le;base64,<?= $this->e(base64_encode($wgConfig)); ?>">
        <?= $this->t('Download config'); ?></a>
    <details>
        <summary><?= $this->e('QR Code'); ?></summary>
        <img alt="WireGuard config QR Code"
             src="<?= $this->e($qrCodeURL); ?>">
    </details>
<? endif; ?>

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

<? if (0 !== count($wgConfigs)): ?>
    <h2><?= $this->t('Existing'); ?></h2>

    <ul class="profileList">
        <? foreach ($wgConfigs as $publicKey => $wgConfig): ?>
            <li>
                <details>
                    <summary
                            title="<?= $this->e($wgConfig->name); ?>"><?= $this->etr($wgConfig->name, 25); ?></summary>
                    <table class="tbl">
                        <tbody>
                        <tr>
                            <th><?= $this->t('Info'); ?></th>
                            <td>
                                <?= $this->e($wgConfig->info); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= $this->t('Last modified at'); /* todo: timezone */ ?></th>
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
                        </tbody>
                    </table>
                </details>
            </li>
        <? endforeach; ?>
    </ul>
<? endif; ?>
<? $this->stop('content'); ?>
