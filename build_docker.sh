#!/bin/bash

echo "Building System Troubleshooter Docker image..."
docker build -t system-troubleshooter .

echo "Running System Troubleshooter..."
docker run -it system-troubleshooter