<?php

class LinuxTroubleshooter
{
    public function printOsVersion()
    {
        echo "=== OS Information ===\n";
        $os = shell_exec('uname -a');
        $version = shell_exec('cat /etc/os-release | grep PRETTY_NAME');
        echo "System: " . trim($os) . "\n";
        echo trim($version) . "\n\n";
    }

    public function printInterfaces()
    {
        echo "=== Network Interfaces ===\n";
        $interfaces = shell_exec('ip addr show | grep -E "^[0-9]+:|inet "');
        echo $interfaces . "\n";
    }

    public function printGateway()
    {
        echo "=== Default Gateway ===\n";
        $gateway = shell_exec('ip route | grep default');
        echo trim($gateway) . "\n\n";
    }

    public function printDnsServers()
    {
        echo "=== DNS Servers ===\n";
        $dns = shell_exec('cat /etc/resolv.conf | grep nameserver');
        echo $dns . "\n";
    }

    public function pingGateway()
    {
        echo "=== Ping Gateway Test ===\n";
        $gateway = trim(shell_exec("ip route | grep default | awk '{print $3}'"));
        if ($gateway) {
            $result = shell_exec("ping -c 4 $gateway");
            echo $result . "\n";
        } else {
            echo "No gateway found\n\n";
        }
    }

    public function pingExternal()
    {
        echo "=== External Connectivity Test (8.8.8.8) ===\n";
        $result = shell_exec('ping -c 4 8.8.8.8');
        echo $result . "\n";
    }

    public function pingDnsResolution()
    {
        echo "=== DNS Resolution Test (google.com) ===\n";
        $result = shell_exec('ping -c 4 google.com');
        echo $result . "\n";
    }

    public function testFirewallPorts()
    {
        echo "=== Firewall/Port Tests ===\n";
        
        echo "Testing HTTPS (443) to google.com:\n";
        $https = shell_exec('timeout 10 bash -c "echo >/dev/tcp/google.com/443" 2>&1 && echo "Port 443 open" || echo "Port 443 blocked"');
        echo trim($https) . "\n\n";
        
        echo "Testing HTTP (80) to google.com:\n";
        $http = shell_exec('timeout 10 bash -c "echo >/dev/tcp/google.com/80" 2>&1 && echo "Port 80 open" || echo "Port 80 blocked"');
        echo trim($http) . "\n\n";
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
    }
}