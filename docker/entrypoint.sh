#!/bin/bash
set -e

# Start Apache immediately in background
echo "Starting Apache..."
apache2-foreground &
APACHE_PID=$!

# Wait a bit for Apache to start
sleep 3

# Run migrations in background (don't block Apache)
(
    echo "Waiting for database..."
    for i in {1..30}; do
        if php artisan migrate:status 2>/dev/null; then
            echo "Database ready, running migrations..."
            php artisan migrate --force
            php artisan storage:link 2>/dev/null || true
            echo "Migrations completed!"
            break
        fi
        echo "Database not ready, waiting... ($i/30)"
        sleep 2
    done
) &

# Wait for Apache process
wait $APACHE_PID
