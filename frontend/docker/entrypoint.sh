#!/bin/sh
# =============================================================================
# Runtime Environment Configuration
# This script replaces environment placeholders in the built JavaScript files
# allowing runtime configuration without rebuilding the Docker image
# =============================================================================

set -e

# Define the directory containing the built files
BUILD_DIR="/usr/share/nginx/html"

# Only run if VITE_API_URL_RUNTIME is set
if [ -n "$VITE_API_URL_RUNTIME" ]; then
    echo "Configuring runtime API URL: $VITE_API_URL_RUNTIME"

    # Find and replace the API URL in JavaScript files
    find "$BUILD_DIR" -type f -name "*.js" -exec sed -i "s|http://localhost:8000|$VITE_API_URL_RUNTIME|g" {} \;
    find "$BUILD_DIR" -type f -name "*.js" -exec sed -i "s|http://backend:8000|$VITE_API_URL_RUNTIME|g" {} \;
fi

echo "Frontend configuration complete"
