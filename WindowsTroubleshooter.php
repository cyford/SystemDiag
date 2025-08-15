<?php

class WindowsTroubleshooter
{
    private $issues = [];
    private $checks = [];
    private $systemInfo = [];
    public function printOsVersion()
    {
        echo "=== OS Information ===\n";
        $os = shell_exec('systeminfo | findstr /B /C:"OS Name" /C:"OS Version"');
        echo $os . "\n";
        $this->checks['os'] = !empty($os) ? 'PASS' : 'FAIL';
        $this->systemInfo['os'] = trim($os);
        if (empty($os)) $this->issues[] = 'OS information unavailable';
    }

    public function printInterfaces()
    {
        echo "=== Network Interfaces ===\n";
        $interfaces = shell_exec('ipconfig /all');
        echo $interfaces . "\n";
        $this->systemInfo['interfaces'] = trim($interfaces);
    }

    public function printGateway()
    {
        echo "=== Default Gateway ===\n";
        $gateway = shell_exec('route print 0.0.0.0');
        echo $gateway . "\n";
        $gatewayIp = trim(shell_exec('for /f "tokens=3" %i in (\'route print ^| findstr "0.0.0.0.*0.0.0.0"\') do @echo %i'));
        $this->systemInfo['gateway'] = $gatewayIp ?: 'Not found';
    }

    public function printDnsServers()
    {
        echo "=== DNS Servers ===\n";
        $dns = shell_exec('nslookup google.com | findstr "Server:"');
        echo $dns . "\n";
        $this->systemInfo['dns'] = trim($dns);
    }

    public function pingGateway()
    {
        echo "=== Ping Gateway Test ===\n";
        $gateway = trim(shell_exec('for /f "tokens=3" %i in (\'route print ^| findstr "0.0.0.0.*0.0.0.0"\') do @echo %i'));
        if ($gateway) {
            $result = shell_exec("ping -n 4 $gateway");
            echo $result . "\n";
            $this->checks['gateway_ping'] = strpos($result, '(0% loss)') !== false ? 'PASS' : 'FAIL';
            if (strpos($result, '(0% loss)') === false) {
                $this->issues[] = 'Gateway unreachable - Check network adapter/WiFi connection';
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
        $result = shell_exec('ping -n 4 8.8.8.8');
        echo $result . "\n";
        $this->checks['external_ping'] = strpos($result, '(0% loss)') !== false ? 'PASS' : 'FAIL';
        if (strpos($result, '(0% loss)') === false) {
            $this->issues[] = 'No external connectivity - Check Windows Firewall/ISP connection';
        }
    }

    public function pingDnsResolution()
    {
        echo "=== DNS Resolution Test (google.com) ===\n";
        $result = shell_exec('ping -n 4 google.com');
        echo $result . "\n";
        $this->checks['dns_resolution'] = strpos($result, '(0% loss)') !== false ? 'PASS' : 'FAIL';
        if (strpos($result, '(0% loss)') === false) {
            $this->issues[] = 'DNS resolution failed - Check DNS servers (8.8.8.8, 1.1.1.1)';
        }
    }

    public function testFirewallPorts()
    {
        echo "=== Firewall/Port Tests ===\n";
        
        echo "Testing HTTPS (443) to google.com:\n";
        $https = shell_exec('powershell -Command "Test-NetConnection google.com -Port 443"');
        echo $https . "\n";
        $this->checks['port_443'] = strpos($https, 'TcpTestSucceeded : True') !== false ? 'PASS' : 'FAIL';
        
        echo "Testing HTTP (80) to google.com:\n";
        $http = shell_exec('powershell -Command "Test-NetConnection google.com -Port 80"');
        echo $http . "\n";
        $this->checks['port_80'] = strpos($http, 'TcpTestSucceeded : True') !== false ? 'PASS' : 'FAIL';
        
        if (strpos($https, 'TcpTestSucceeded : True') === false) {
            $this->issues[] = 'HTTPS (443) blocked - Check Windows Firewall rules';
        }
        if (strpos($http, 'TcpTestSucceeded : True') === false) {
            $this->issues[] = 'HTTP (80) blocked - Check Windows Firewall rules';
        }
    }

    public function checkWindowsFirewall()
    {
        echo "=== Windows Firewall Status ===\n";
        $firewall = shell_exec('netsh advfirewall show allprofiles state');
        echo $firewall . "\n";
    }

    public function displayConnectedDevices()
    {
        echo "=== Connected Devices (ARP Table) ===\n";
        $arp = shell_exec('arp -a');
        echo $arp . "\n";
        
        echo "=== Network Connections ===\n";
        $netstat = shell_exec('netstat -an | findstr LISTENING');
        echo $netstat . "\n";
    }

    public function printSummary()
    {
        echo "\n=== SYSTEM DETAILS SUMMARY ===\n";
        echo "OS: " . ($this->systemInfo['os'] ?? 'Unknown') . "\n";
        echo "Gateway: " . ($this->systemInfo['gateway'] ?? 'Not found') . "\n";
        echo "DNS: " . ($this->systemInfo['dns'] ?? 'Not configured') . "\n";
        
        echo "\n=== NETWORK INTERFACES (Summary) ===\n";
        $interfaces = $this->systemInfo['interfaces'] ?? 'No interfaces found';
        // Show only active adapters summary
        $lines = explode("\n", $interfaces);
        foreach ($lines as $line) {
            if (strpos($line, 'adapter') !== false || strpos($line, 'IPv4') !== false) {
                echo trim($line) . "\n";
            }
        }
        
        echo "\n=== TEST RESULTS ===\n";
        foreach ($this->checks as $check => $status) {
            $symbol = $status === 'PASS' ? 'v' : 'X';
            echo "  $symbol " . ucwords(str_replace('_', ' ', $check)) . ": $status\n";
        }
        
        if (!empty($this->issues)) {
            echo "\nISSUES FOUND:\n";
            foreach ($this->issues as $i => $issue) {
                echo "  " . ($i + 1) . ". $issue\n";
            }
            
            echo "\nTROUBLESHOOTING STEPS:\n";
            echo "  1. Check network adapter status in Device Manager\n";
            echo "  2. Reset network: netsh winsock reset\n";
            echo "  3. Flush DNS: ipconfig /flushdns\n";
            echo "  4. Reset TCP/IP: netsh int ip reset\n";
            echo "  5. Check Windows Firewall settings\n";
            echo "  6. Restart network adapter or reboot system\n";
        } else {
            echo "\nv All network tests PASSED!\n";
        }
        echo "\n";
    }

    public function runAllTests()
    {
        echo "Windows System Network Troubleshooter\n";
        echo "====================================\n\n";
        
        $this->printOsVersion();
        $this->printInterfaces();
        $this->printGateway();
        $this->printDnsServers();
        $this->pingGateway();
        $this->pingExternal();
        $this->pingDnsResolution();
        $this->testFirewallPorts();
        $this->checkWindowsFirewall();
        $this->displayConnectedDevices();
        $this->printSummary();
    }
}