<?php

class LinuxTroubleshooter
{
    private $issues = [];
    private $checks = [];
    private $systemInfo = [];
    public function printOsVersion()
    {
        echo "=== OS Information ===\n";
        $os = shell_exec('uname -a');
        $version = shell_exec('cat /etc/os-release | grep PRETTY_NAME');
        echo "System: " . trim($os) . "\n";
        echo trim($version) . "\n\n";
        $this->checks['os'] = !empty($os) ? 'PASS' : 'FAIL';
        $this->systemInfo['os'] = trim($os);
        $this->systemInfo['version'] = trim($version);
        if (empty($os)) $this->issues[] = 'OS information unavailable';
    }

    public function printInterfaces()
    {
        echo "=== Network Interfaces ===\n";
        $interfaces = shell_exec('ip addr show | grep -E "^[0-9]+:|inet "');
        echo $interfaces . "\n";
        $this->systemInfo['interfaces'] = trim($interfaces);
    }

    public function printGateway()
    {
        echo "=== Default Gateway ===\n";
        $gateway = shell_exec('ip route | grep default');
        echo trim($gateway) . "\n\n";
        $this->systemInfo['gateway'] = trim($gateway);
    }

    public function printDnsServers()
    {
        echo "=== DNS Servers ===\n";
        $dns = shell_exec('cat /etc/resolv.conf | grep nameserver');
        echo $dns . "\n";
        $this->systemInfo['dns'] = trim($dns);
    }

    public function pingGateway()
    {
        echo "=== Ping Gateway Test ===\n";
        $gateway = trim(shell_exec("ip route | grep default | awk '{print $3}'"));
        if ($gateway) {
            $result = shell_exec("ping -c 4 $gateway");
            echo $result . "\n";
            $this->checks['gateway_ping'] = strpos($result, '0% packet loss') !== false ? 'PASS' : 'FAIL';
            if (strpos($result, '0% packet loss') === false) {
                $this->issues[] = 'Gateway unreachable - Check network cable/WiFi connection';
            }
        } else {
            echo "No gateway found\n\n";
            $this->checks['gateway_ping'] = 'FAIL';
            $this->issues[] = 'No default gateway configured - Check network settings';
        }
    }

    public function pingExternal()
    {
        echo "=== External Connectivity Test (8.8.8.8) ===\n";
        $result = shell_exec('ping -c 4 8.8.8.8');
        echo $result . "\n";
        $this->checks['external_ping'] = strpos($result, '0% packet loss') !== false ? 'PASS' : 'FAIL';
        if (strpos($result, '0% packet loss') === false) {
            $this->issues[] = 'No external connectivity - Check firewall/ISP connection';
        }
    }

    public function pingDnsResolution()
    {
        echo "=== DNS Resolution Test (google.com) ===\n";
        $result = shell_exec('ping -c 4 google.com');
        echo $result . "\n";
        $this->checks['dns_resolution'] = strpos($result, '0% packet loss') !== false ? 'PASS' : 'FAIL';
        if (strpos($result, '0% packet loss') === false) {
            $this->issues[] = 'DNS resolution failed - Check DNS servers (8.8.8.8, 1.1.1.1)';
        }
    }

    public function testFirewallPorts()
    {
        echo "=== Firewall/Port Tests ===\n";
        
        echo "Testing HTTPS (443) to google.com:\n";
        $https = shell_exec('timeout 10 bash -c "echo >/dev/tcp/google.com/443" 2>&1 && echo "Port 443 open" || echo "Port 443 blocked"');
        echo trim($https) . "\n\n";
        $this->checks['port_443'] = strpos($https, 'open') !== false ? 'PASS' : 'FAIL';
        
        echo "Testing HTTP (80) to google.com:\n";
        $http = shell_exec('timeout 10 bash -c "echo >/dev/tcp/google.com/80" 2>&1 && echo "Port 80 open" || echo "Port 80 blocked"');
        echo trim($http) . "\n\n";
        $this->checks['port_80'] = strpos($http, 'open') !== false ? 'PASS' : 'FAIL';
        
        if (strpos($https, 'open') === false) {
            $this->issues[] = 'HTTPS (443) blocked - Check firewall rules';
        }
        if (strpos($http, 'open') === false) {
            $this->issues[] = 'HTTP (80) blocked - Check firewall rules';
        }
    }

    public function displayConnectedDevices()
    {
        echo "=== Connected Devices (ARP Table) ===\n";
        $arp = shell_exec('arp -a');
        echo $arp . "\n";
        
        echo "=== Network Connections ===\n";
        $netstat = shell_exec('netstat -tuln | head -20');
        echo $netstat . "\n";
    }

    public function checkSecurityModules()
    {
        echo "=== Security Modules Status ===\n";
        
        // Check SELinux
        echo "SELinux Status:\n";
        $selinux = shell_exec('which getenforce > /dev/null 2>&1 && getenforce || echo "SELinux not installed"');
        echo trim($selinux) . "\n";
        
        if (trim($selinux) !== "SELinux not installed") {
            $sestatus = shell_exec('sestatus 2>/dev/null | head -5');
            echo $sestatus;
        }
        
        // Check AppArmor
        echo "\nAppArmor Status:\n";
        $apparmor = shell_exec('which aa-status > /dev/null 2>&1 && aa-status --enabled && echo "AppArmor enabled" || echo "AppArmor disabled/not installed"');
        echo trim($apparmor) . "\n";
        
        if (strpos($apparmor, "enabled") !== false) {
            $aa_profiles = shell_exec('aa-status 2>/dev/null | head -10');
            echo $aa_profiles;
        }
        
        echo "\n";
    }

    public function printSummary()
    {
        echo "\n=== SYSTEM DETAILS SUMMARY ===\n";
        echo "OS: " . ($this->systemInfo['os'] ?? 'Unknown') . "\n";
        echo "Version: " . ($this->systemInfo['version'] ?? 'Unknown') . "\n";
        echo "Gateway: " . ($this->systemInfo['gateway'] ?? 'Not found') . "\n";
        echo "DNS: " . ($this->systemInfo['dns'] ?? 'Not configured') . "\n";
        
        echo "\n=== NETWORK INTERFACES ===\n";
        echo ($this->systemInfo['interfaces'] ?? 'No interfaces found') . "\n";
        
        echo "\n=== TEST RESULTS ===\n";
        foreach ($this->checks as $check => $status) {
            $symbol = $status === 'PASS' ? '✓' : '✗';
            echo "  $symbol " . ucwords(str_replace('_', ' ', $check)) . ": $status\n";
        }
        
        if (!empty($this->issues)) {
            echo "\nISSUES FOUND:\n";
            foreach ($this->issues as $i => $issue) {
                echo "  " . ($i + 1) . ". $issue\n";
            }
            
            echo "\nTROUBLESHOOTING STEPS:\n";
            echo "  1. Check physical connections (cables, WiFi)\n";
            echo "  2. Restart network service: sudo systemctl restart networking\n";
            echo "  3. Check firewall: sudo ufw status\n";
            echo "  4. Flush DNS: sudo systemctl restart systemd-resolved\n";
            echo "  5. Check routes: ip route show\n";
            echo "  6. Contact network administrator if issues persist\n";
        } else {
            echo "\n✓ All network tests PASSED!\n";
        }
        echo "\n";
    }

    public function runAllTests()
    {
        echo "Linux System Network Troubleshooter\n";
        echo "===================================\n\n";
        
        $this->printOsVersion();
        $this->printInterfaces();
        $this->printGateway();
        $this->printDnsServers();
        $this->pingGateway();
        $this->pingExternal();
        $this->pingDnsResolution();
        $this->testFirewallPorts();
        $this->checkSecurityModules();
        $this->displayConnectedDevices();
        $this->printSummary();
    }
}