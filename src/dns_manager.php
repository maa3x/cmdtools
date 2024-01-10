#!/usr/bin/env php
<?php

use Illuminate\Support\Str;
use Laravel\Prompts\Table;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;
use function Laravel\Prompts\error;
use function Laravel\Prompts\table;
use function Laravel\Prompts\multiselect;

require_once __DIR__ . '/../vendor/autoload.php';
system('clear');
info('Ma3X\'s DNS Activator');
$netservices  = str(`networksetup -listallnetworkservices`)->after("\n")->trim()->explode("\n")->filter(fn($s) => !Str::contains($s, 'Streisand'))->map(fn($s) => trim($s));
foreach($netservices as $svc) {
    info($svc);

    $dns = trim(`networksetup -getdnsservers $svc`);
    $info = [
        $dns == "There aren't any DNS Servers set on $svc." ? 'Off' : $dns,
        str(trim(`networksetup -getwebproxy $svc`))->beforeLast("\n"),
        str(trim(`networksetup -getsecurewebproxy $svc`))->beforeLast("\n"),
        str(trim(`networksetup -getsocksfirewallproxy $svc`))->beforeLast("\n"),
        trim(`networksetup -getinfo $svc`),
    ];
    table(['dns', 'http proxy', 'https proxy', 'socks5 proxy', 'info'], [$info]);
}

$interfaces = multiselect(
    label: 'Select network interfaces',
    options: $netservices,
    default: ['Wi-Fi'],
);
if (count($interfaces) == 0) {
    exit;
}

$services = multiselect(
    label: 'Select DNS service provider',
    options: [
    'shecan' => 'Shecan (178.22.122.100, 185.51.200.2)',
    'electro' => 'Electro (78.157.42.100, 78.157.42.101)',
    '403' => '403 (10.202.10.202, 10.202.10.102)',
    'radar' => 'Radar (10.202.10.10, 10.202.10.11)',
    'begzar' => 'Begzar (185.55.226.26, 185.55.225.25)',
    'google' => 'Google (8.8.8.8, 8.8.4.4) ',
    'cloudflare' => 'CloudFlare (1.1.1.1, 1.0.0.1)',
    'other' => 'Other (custom address)'
  ],
    default: ['shecan'],
    scroll: 9
);

$addresses = [];
foreach ($services as $s) {
    $addresses = array_merge($addresses, match ($s) {
        'google' => ['8.8.8.8', '8.8.4.4'],
        'cloudflare' => ['1.1.1.1', '1.0.0.1'],
        'shecan' => ['178.22.122.100', '185.51.200.2'],
        '403' => ['10.202.10.202', '10.202.10.102'],
        'radar' => ['10.202.10.10', '10.202.10.11'],
        'begzar' => ['185.55.226.26', '185.55.225.25'],
        'electro' => ['78.157.42.100', '78.157.42.101'],
        'other' => getCustomAddress(),
    });
}
if (count($addresses) == 0) {
    $addresses = ['empty'];
}

function getCustomAddress()
{
    $svc = text(
        label: 'Enter DNS service address',
        placeholder: '1.2.3.4, 5.6.7.8',
        hint: 'separate multiple values by comma',
        required: true,
    );

    $addrs = [];
    foreach (explode(',', $svc) as $s) {
        $filtered = filter_var($s, FILTER_VALIDATE_IP);
        if ($filtered === false) {
            error("\"$s\" is not a valid IP address!");
            return getCustomAddress();
        }

        $addrs[] = trim($s);
    }

    return $addrs;
}

foreach ($interfaces as $i) {
    $s = implode(' ', [$i, ...$addresses]);
    note($s);
    $out = shell_exec(sprintf('networksetup -setdnsservers %s', $s));
    if ($out) {
        error($out);
    }

}

info('DONE!');
