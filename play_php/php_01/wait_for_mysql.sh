#!/bin/sh
host="$1"
port="$2"
shift 2

echo "⏳ Waiting for MySQL at $host:$port..."
retries=10
count=0
until mysqladmin ping -h "$host" -P "$port" -uroot -p123 --silent --ssl-mode=DISABLED 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $retries ]; then
        echo "❌ Unable to connect to MySQL at $host:$port after $retries retries"
        exit 1
    fi
    sleep 3
done

echo "✅ MySQL is up! Starting Python server..."
exec "$@"