<?php

/* @var LC\Portal\Tpl $this */

/* @var string        $hostName
 * @var int           $port
 * @var string        $clientIp
 * @var string        $serverPublicKey
 * @var string|null   $clientPrivateKey
 * @var array<string> $dnsServers
 */ ?>
[Interface]
<?php if (is_string($clientPrivateKey)): ?>
PrivateKey = <?= $clientPrivateKey; ?>

<?php endif; ?>
DNS = <?= implode(',', $dnsServers); ?>

Address = <?= $clientIp; ?>

[Peer]
PublicKey = <?= $serverPublicKey; ?>

AllowedIPs = 0.0.0.0/0
Endpoint = <?= $hostName.':'.$port; ?>

