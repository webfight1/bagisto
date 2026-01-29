#!/bin/bash

# Configuration
VPS_HOST="root@45.93.139.96"
VPS_PATH="/root/bagisto"
CONTAINER_NAME="bagisto-app"
IMAGE_NAME="bagisto-app"
PORT="8086"
NETWORK="crm_network"

# Generate version tag (timestamp)
VERSION=$(date +"%Y%m%d%H%M")
IMAGE_TAG="${IMAGE_NAME}:${VERSION}"

echo "🚀 Starting Bagisto deployment process (server-side build)..."
echo "📦 Version: ${VERSION}"

# Create exclude file for rsync (ignore large/unnecessary files)
cat > /tmp/bagisto-rsync-exclude << 'EOF'
.git/
.github/
node_modules/
vendor/
storage/logs/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
bootstrap/cache/
.env
.DS_Store
*.log
*.tar
deploy.log
EOF

# Sync source code to VPS (excluding large dirs)
echo "📤 Syncing source code to VPS..."
rsync -avz --delete \
  --exclude-from=/tmp/bagisto-rsync-exclude \
  --progress \
  ./ ${VPS_HOST}:${VPS_PATH}/src/

if [ $? -ne 0 ]; then
    echo "❌ Failed to sync files to VPS!"
    exit 1
fi

# Build and deploy on VPS
echo "🔨 Building and deploying on VPS..."
ssh ${VPS_HOST} << EOF
cd ${VPS_PATH}/src

echo "Building Docker image version ${VERSION}..."
docker build --platform linux/amd64 -t ${IMAGE_TAG} .

if [ \$? -ne 0 ]; then
    echo "❌ Docker build failed!"
    exit 1
fi

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

if [ \$? -ne 0 ]; then
    echo "❌ Failed to start container!"
    exit 1
fi

echo "Waiting for container to be ready..."
sleep 5

echo "Running database migrations..."
docker exec ${CONTAINER_NAME} php artisan migrate --force

echo "Cleaning up old images..."
docker images | grep ${IMAGE_NAME} | grep -v ${VERSION} | awk '{print \$3}' | xargs -r docker rmi 2>/dev/null || true

echo "Cleaning up..."
docker system prune -f

echo "✅ Deployment of version ${VERSION} completed successfully!"
echo ""
echo "🔄 Container status:"
docker ps --filter name=${CONTAINER_NAME} --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
EOF

# Clean up local temp file
rm -f /tmp/bagisto-rsync-exclude

echo ""
echo "✨ Deployment process completed!"
echo "🌐 Your application is now live at http://45.93.139.96:${PORT}"
echo "📌 Deployed version: ${VERSION} (${IMAGE_TAG})"
