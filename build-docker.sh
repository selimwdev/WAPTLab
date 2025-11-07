#!/usr/bin/env bash
set -e

docker-compose down --remove-orphans
docker-compose build --no-cache
docker-compose up -d

echo "Waiting for http://localhost:8000 ..."
for i in {1..30}; do
  if curl -fsS http://localhost:8000/health >/dev/null 2>&1 || curl -fsS http://localhost:8000 >/dev/null 2>&1; then
    echo "✅ Health: http://localhost:8000"
    exit 0
  fi
  sleep 2
done

echo "App did not become healthy in time. See logs: docker-compose logs -f"
exit 1
