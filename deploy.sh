iu#!/bin/bash

# Configuration
VPS_HOST="root@45.93.139.96"
VPS_PATH="/root/bagisto"
CONTAINER_NAME="bagisto-app"
IMAGE_NAME="bagisto-app"
PORT="8086"
DB_HOST="crm_mysql"
DB_PORT="3306"
DB_NAME="bagisto"
DB_USER="laravel"
DB_PASS="Veebileht2025!"
NETWORK="crm_network"

# Generate version tag (timestamp)
VERSION=$(date +"%Y%m%d%H%M")
IMAGE_TAG="${IMAGE_NAME}:${VERSION}"

echo "🚀 Starting Bagisto deployment process..."
echo "📦 Building version: ${VERSION}"

# Build Docker image for AMD64 platform
echo "🔨 Building Docker image for AMD64..."
docker build --platform linux/amd64 -t ${IMAGE_TAG} .

if [ $? -ne 0 ]; then
    echo "❌ Docker build failed!"
    exit 1
fi

# Save image to tar file
echo "💾 Saving Docker image to tar file..."
docker save ${IMAGE_TAG} -o ${IMAGE_NAME}-${VERSION}.tar

# Transfer to VPS
echo "📤 Transferring image to VPS..."
scp ${IMAGE_NAME}-${VERSION}.tar ${VPS_HOST}:${VPS_PATH}/

# Deploy on VPS
echo "🚀 Deploying to VPS..."
ssh ${VPS_HOST} << EOF
cd ${VPS_PATH}

echo "Loading Docker image version ${VERSION}..."
docker load -i ${IMAGE_NAME}-${VERSION}.tar

echo "Creating directories..."
mkdir -p storage logs

echo "Setting permissions..."
chmod -R 775 storage logs

echo "Stopping and removing existing container..."
docker stop ${CONTAINER_NAME} 2>/dev/null || true
docker rm ${CONTAINER_NAME} 2>/dev/null || true

echo "Starting new container with version ${VERSION}..."
docker run -d \
  --name ${CONTAINER_NAME} \
  --network ${NETWORK} \
  -p ${PORT}:80 \
  --restart unless-stopped \
  ${IMAGE_TAG}

echo "Cleaning up old images..."
docker images | grep ${IMAGE_NAME} | grep -v ${VERSION} | awk '{print \$3}' | xargs -r docker rmi 2>/dev/null || true

echo "Cleaning up..."
docker system prune -f

echo "Running database migrations..."
docker exec ${CONTAINER_NAME} php artisan migrate --force

echo "✅ Deployment of version ${VERSION} completed successfully!"
echo ""
echo "🔄 Running container status:"
docker ps --filter name=${CONTAINER_NAME} --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Clean up tar file
rm -f ${IMAGE_NAME}-${VERSION}.tar
EOF

# Clean up local files
echo "🧹 Cleaning up local files..."
rm -f ${IMAGE_NAME}-${VERSION}.tar

echo "✨ Deployment process completed! Your application is now live at http://45.93.139.96:${PORT}"
echo "📌 Deployed version: ${VERSION} (${IMAGE_TAG})"
