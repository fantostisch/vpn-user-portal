<?php $this->layout('base', ['activeItem' => 'certificates']); ?>
<?php $this->start('content'); ?>
    <?php if (0 === count($userCertificateList)): ?>
        <p class="plain">
            <?=$this->t('There are currently no issued certificates. <a href="new">Download</a> a new configuration.'); ?>
        </p>                    
    <?php else: ?>
        <table>
            <thead>
                <tr><th><?=$this->t('Name'); ?></th><th><?=$this->t('Issued'); ?> (<?=$this->e(date('T')); ?>)</th><th><?=$this->t('Expires'); ?> (<?=$this->e(date('T')); ?>)</th><th></th></tr> 
            </thead>
            <tbody>
            <?php foreach ($userCertificateList as $userCertificate): ?>
                <tr>
                    <td><?=$this->e($userCertificate['display_name']); ?></td>
                    <td><?=$this->e($userCertificate['valid_from']); ?></td>
                    <td><?=$this->e($userCertificate['valid_to']); ?></td>
                    <td class="text-right">
                        <form method="post" class="inline" action="deleteCertificate">
                            <input type="hidden" name="commonName" value="<?=$this->e($userCertificate['common_name']); ?>">
                            <button type="submit" class="error"><?=$this->t('Delete'); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php $this->stop(); ?>
