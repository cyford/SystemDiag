# System Troubleshooter

PHP CLI tool for Linux system and network diagnostics.

## Docker Usage

### Build and run:
```bash
docker build -t system-troubleshooter .
docker run -it system-troubleshooter
```

### Using docker-compose:
```bash
# Run all tests
docker-compose run troubleshooter

# Run specific test
docker-compose run troubleshooter php troubleshoot.php interfaces

# Interactive shell
docker-compose run troubleshooter bash
```

### Manual PHP execution:
```bash
# All tests
php index.php

# Individual tests
php troubleshoot.php [os|interfaces|gateway|dns|ping-gateway|ping-external|ping-dns|firewall|devices]
```