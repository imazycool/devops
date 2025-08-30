# setup doker 
## create images
docker build --target db -t my-db .
docker build --target app -t my-frontend .
docker build --target php-app -t my-frontend .


# create bridge network 
docker network create student-net 

# start mariadb container 
docker run -d --name mariadb-student --network student-net -p 3307:3306 -e MYSQL_ALLOW_EMPTY_PASSWORD=yes  my-db  

# start app nginx-alpine server with source code 
docker run -d --name php-app --network student-net -p 8080:80 my-frontend 
-- docker run -d --name php-app --network student-net -p 8080:80  php:apache

## in php server 
we may have to install if not added in dockerfile  
apk add --no-cache mariadb-dev
docker-php-ext-install mysqli pdo pdo_mysql
docker-php-ext-enable mysqli 
then restart the container 


## for database side there were many version related challanges 
Importing Data
	1.	Generate SQL dump from local MySQL/XAMPP
    sudo /Applications/XAMPP/xamppfiles/bin/mysqldump -u root student > student.sql
    - this student.sql is stored inside app directory 

    - this is also fixed 
        $servername = "mariadb-student";  // container name
        $conn = new mysqli($servername, "root", "", "student");

## the tar back up is also saved under app/image-back_up directory 
anyone can load it by below command 
docker load < php-app.tar
docker load < mariadb-app.tar

## added new dockerfile for grpc docker
- all grpc related codes are placed inside service directory  
- install python depandencies listed in requirement.txt 
- run this command to generate message and service 
- python3 -m grpc_tools.protoc -I. --python_out=.  --grpc_python_out=.  play_php/php_01/service/student.proto
- student.proto is there inside 


# date 25 August post microservice changes 
1) Repository layout
    php_01/
├─ app/
│  ├─ client_user/
│  │  ├─ fac_gen.php            # gRPC-only PHP page (final)
│  │  └─ ... your other PHP files ...
│  ├─ admin/ …                  # your existing app
│  ├─ database/ …
│  └─ student.sql               # MySQL dump (compatible with MySQL 8)
├─ student.proto                 # final proto
├─ student_service.py            # final Python gRPC service
├─ requirements.txt              # Python deps (final)
├─ dockerfile                    # multi-stage: python service + php+nginx
├─ docker-compose.yaml           # services: mysql, php-app, student-grpc
├─ nginx.conf                    # main nginx
├─ default.conf                  # site server config (used by our image)
├─ start.sh                      # starts php-fpm then nginx (in php image)
├─ composer.json                 # PHP deps (gRPC/protobuf)
└─ php_client/                   # Generated PHP stubs (committed)
   ├─ GPBMetadata/Student.php
   └─ Student/
      ├─ Student.php
      ├─ StudentListResponse.php
      ├─ StudentRequest.php
      ├─ StudentResponse.php
      └─ StudentServiceClient.php


2) docker-compose.yaml (final)
    services:
  mariadb:
    image: mysql:8.0
    container_name: mariadb
    environment:
      MYSQL_ROOT_PASSWORD: root123
      MYSQL_DATABASE: student
      MYSQL_USER: student_user
      MYSQL_PASSWORD: student_pass
    ports:
      - "3307:3306"
    volumes:
      - mariadb-data:/var/lib/mysql
      - ./app/student.sql:/docker-entrypoint-initdb.d/student.sql:ro
    networks:
      - student-net
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-hmariadb", "-ustudent_user", "-pstudent_pass"]
      interval: 5s
      retries: 10

  student-grpc:
    build:
      context: .
      target: db-service
    container_name: student-grpc
    depends_on:
      mariadb:
        condition: service_healthy
    networks:
      - student-net
    ports:
      - "50051:50051"

  php-app:
    build:
      context: .
      target: php-app
    container_name: php-app
    ports:
      - "8080:80"
    volumes:
      - php-data:/var/www/html   # lets you tweak PHP code live
    depends_on:
      - mariadb
      - student-grpc
    networks:
      - student-net

networks:
  student-net:
    name: student-net
    driver: bridge

volumes:
  mariadb-data:
  php-data:


3) dockerfile (final)
## ================================
## gRPC Microservice (Python)
## ================================
FROM python:3.11-slim AS db-service
WORKDIR /app

# (optional) mysql client for debug
RUN apt-get update && apt-get install -y mariadb-client && rm -rf /var/lib/apt/lists/*

# Python deps
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# service code + proto (python side already generated in your files)
COPY student_service.py student.proto student_pb2.py student_pb2_grpc.py ./

EXPOSE 50051
CMD ["python", "student_service.py"]


## ================================
## PHP + Nginx App
## ================================
FROM php:8.2-fpm-bullseye AS php-app

# Nginx + PHP extensions + tools
RUN apt-get update && apt-get install -y \
    nginx \
    mariadb-client libmariadb-dev \
    autoconf make g++ libtool pkg-config \
    procps curl git unzip \
 && docker-php-ext-install mysqli pdo pdo_mysql \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
      --install-dir=/usr/local/bin --filename=composer

# App code & configs
COPY ./app /var/www/html
COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/conf.d/default.conf
COPY start.sh /start.sh
RUN chmod +x /start.sh

# PHP dependencies (grpc/grpc + google/protobuf)
WORKDIR /var/www/html
COPY composer.json composer.lock* /var/www/html/
RUN composer install --no-dev --optimize-autoloader

# Pre-generated PHP gRPC stubs (commit them so we don't need protoc)
# Path matches the "php_client" includes used by fac_gen.php
COPY php_client /var/www/html/php_client

# Normalize line endings for PHP sources (safe-guard)
RUN apt-get update && apt-get install -y dos2unix \
 && find /var/www/html -type f -name "*.php" -exec dos2unix {} \; \
 && apt-get purge -y --auto-remove dos2unix && apt-get clean

EXPOSE 80
CMD ["/start.sh"]


4) student.proto (final)
syntax = "proto3";

package Student;

import "google/protobuf/empty.proto";

service StudentService {
  rpc GetStudent (StudentRequest) returns (StudentResponse);
  rpc ListStudents (google.protobuf.Empty) returns (StudentListResponse);
}

message StudentRequest {
  int32 id = 1;
}

message StudentResponse {
  Student student = 1;
}

message StudentListResponse {
  repeated Student students = 1;
}

message Student {
  int32 id = 1;
  string name = 2;
  string department = 3;
}


5) student_service.py (final)
import time
from concurrent import futures
import grpc
from grpc_reflection.v1alpha import reflection

import mysql.connector
from mysql.connector import Error

import student_pb2
import student_pb2_grpc

DB_CONFIG = dict(
    host="mariadb",
    user="student_user",
    password="student_pass",
    database="student",
    port=3306,
    autocommit=True,
)

def connect_with_retries(max_retries=30, delay=3):
    for attempt in range(1, max_retries + 1):
        try:
            conn = mysql.connector.connect(**DB_CONFIG)
            print(" Connected to MySQL!", flush=True)
            return conn
        except Error as e:
            print(f"⏳ Waiting for MySQL... try {attempt}/{max_retries} ({e})", flush=True)
            time.sleep(delay)
    raise RuntimeError(" Unable to connect to MySQL after retries")

class StudentService(student_pb2_grpc.StudentServiceServicer):
    def __init__(self):
        self.conn = connect_with_retries()

    def _ensure_conn(self):
        try:
            self.conn.ping(reconnect=True, attempts=3, delay=1)
        except Error:
            self.conn = connect_with_retries()

    def GetStudent(self, request, context):
        self._ensure_conn()
        cur = self.conn.cursor()
        try:
            cur.execute("SELECT id, name, standard FROM admission WHERE id=%s", (request.id,))
            row = cur.fetchone()
            if not row:
                context.set_code(grpc.StatusCode.NOT_FOUND)
                context.set_details("Student not found")
                return student_pb2.StudentResponse()
            student = student_pb2.Student(id=row[0], name=row[1], department=row[2])
            return student_pb2.StudentResponse(student=student)
        finally:
            cur.close()

    def ListStudents(self, request, context):
        self._ensure_conn()
        cur = self.conn.cursor()
        try:
            cur.execute("SELECT id, name, standard FROM admission")
            rows = cur.fetchall()
            students = [student_pb2.Student(id=r[0], name=r[1], department=r[2]) for r in rows]
            return student_pb2.StudentListResponse(students=students)
        finally:
            cur.close()

def serve():
    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    student_pb2_grpc.add_StudentServiceServicer_to_server(StudentService(), server)

    SERVICE_NAMES = (
        student_pb2.DESCRIPTOR.services_by_name["StudentService"].full_name,
        reflection.SERVICE_NAME,
    )
    reflection.enable_server_reflection(SERVICE_NAMES, server)

    server.add_insecure_port("[::]:50051")
    print("🚀 StudentService running at :50051", flush=True)
    server.start()
    server.wait_for_termination()

if __name__ == "__main__":
    serve()


6) requirements.txt (final)
grpcio==1.65.1
grpcio-tools==1.65.1
protobuf==4.25.3
mysql-connector-python==8.3.0
grpcio-reflection==1.65.1


8) composer.json (final)
{
  "require": {
    "grpc/grpc": "^1.74",
    "google/protobuf": "^4.25"
  },
  "autoload": {
    "classmap": [
      "php_client/"
    ]
  }
}

9) nginx.conf (final minimal)
events {}

http {
  include       mime.types;
  default_type  application/octet-stream;
  sendfile      on;
  keepalive_timeout  65;

  server {
    listen 80;
    server_name _;

    root /var/www/html;
    index index.php index.html;

    location / {
      try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
      include fastcgi_params;
      fastcgi_pass 127.0.0.1:9000;  # php-fpm in same container
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_read_timeout 300;
    }
  }
}
11) start.sh (final)
            #!/bin/sh
            set -e

            # start php-fpm if not running
            if ! pgrep -x "php-fpm" >/dev/null 2>&1; then
            php-fpm -D
            fi

            # run nginx in foreground
            nginx -g 'daemon off;'

- chmod +x start.sh


12) student.sql (final notes)
	•	Should be compatible with MySQL 8.0 (avoid utf8mb4_0900_ai_ci if you export from newer MySQL; use utf8mb4_general_ci or plain latin1 like your current dump).
	•	Your working dump has admission, staff, standard, user.
	•	The Python service currently reads admission (as “students”). That’s what you validated.

13) Build & Run (final)
docker compose build --no-cache
docker compose up -d
docker compose ps

14) .gitignore (to avoid big files)
        # docker artifacts
        *.tar
        *.img

        # composer
        /vendor/
        /composer.lock

        # python
        __pycache__/
        *.pyc

        # misc
        .DS_Store


It captures the fixes we made over the last sessions:
	•	Switched to MySQL 8.0 with explicit student_user/student_pass.
	•	Cleaned student.sql to avoid MariaDB collation issues.
	•	Robust Python gRPC service with connection retries, per-request cursors, auto-reconnect, and reflection.
	•	PHP frontend fully switched to gRPC; removed mysqli; clean and styled page.
	•	Slim, reproducible Dockerfiles and docker-compose.
	•	Pre-generated PHP stubs committed, no protoc needed in the PHP image.
	•	Healthcheck on DB, ordered startup.

# ###########################################################################################
# The Struggles & Fixes for php-app gRPC Setup

1. PHP was trying direct MySQL (mysqli) instead of gRPC
	•	Problem:
Original PHP files (like fac_gen.php) were doing
- $conn = new mysqli("mariadb-student", "root", "", "student");
which failed (php_network_getaddresses: getaddrinfo for mariadb-student failed).
- Fix: Replaced all DB calls with gRPC client calls.
    $client = new StudentServiceClient('student-grpc:50051', [
    'credentials' => Grpc\ChannelCredentials::createInsecure(),
    ]);

2. Composer autoload not found (vendor/autoload.php)
	•	Problem:
        Fatal error: Failed opening required 'vendor/autoload.php' 
        because Composer hadn’t been initialized/installed.

	•   Fix:
	    - Added Composer installation in Dockerfile:
        RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

    •   Added a valid composer.json:
        {
        "require": {
            "grpc/grpc": "^1.74",
            "google/protobuf": "^4.25"
        },
        "autoload": {
            "classmap": [
            "php_client/"
            ]
        }
        }

    •	Ran composer install in php-app stage

3. Missing gRPC PHP client stubs
	•	Problem: Errors like: Class "StudentServiceClient" not found
                    require /generated/Student/Student.php not found
        because protoc-generated PHP stubs weren’t present.
    •	Fix:
        •	Installed protoc + grpc_php_plugin (struggled a lot with arm64 vs amd64).
        •	Generated stubs once with:
            protoc --proto_path=. \
                --php_out=php_client \
                --grpc_out=php_client \
                --plugin=protoc-gen-grpc=/usr/local/bin/grpc_php_plugin \
                student.proto
        •	Decided to commit php_client/ into repo so we don’t rebuild protoc every time.
	    •	Adjusted PHP includes:    
        $genBase = __DIR__ . '/../php_client';
        foreach (glob($genBase . '/GPBMetadata/*.php') as $f) require_once $f;
        foreach (glob($genBase . '/Student/*.php') as $f)     require_once $f;


4. PHP gRPC client pointing to wrong host
	•	Problem:
            We used 'localhost:50051' inside PHP container → couldn’t reach the Python gRPC server (different container).
	•	Fix:
            Changed to Docker network hostname:
                new StudentServiceClient('student-grpc:50051', [...])
        it resolves to the gRPC service container name.

5. “Method not found!” error from gRPC
	•	Problem:
            Even after stubs were in place, calling ListStudents returned:
                gRPC error: Method not found!
            because server only implemented GetStudent.
    •	Fix:
        •	Updated student.proto to include ListStudents.
        •	Implemented ListStudents in student_service.py.
        •	Regenerated stubs for PHP/Python.

⸻

6. Mix of gRPC and old MySQL code
	•	Problem:
            fac_gen.php was mixing mysqli (with $conn->query) and gRPC client calls → duplicate output + undefined $conn warnings.
	•	Fix:
        •	Removed all direct MySQL code.
        •	Replaced with pure gRPC call:
                [$resp, $status] = $client->ListStudents(new GPBEmpty())->wait();
                foreach ($resp->getStudents() as $s) {
                    echo $s->getName();
                }
7. Header issues & BOM problem
	•	Problem:
            Cannot modify header information - headers already sent...
            due to hidden UTF-8 BOM in fac_gen.php.
	•	Fix:
	        Removed BOM:
                sed -i '1s/^\xEF\xBB\xBF//' fac_gen.php
                dos2unix fac_gen.php
            Ensured <?php is the very first bytes in the file.

8. Reflection not supported in gRPC debug
	•	Problem:
            grpcurl list failed (server does not support reflection).
	•	Fix:
            Enabled reflection in Python service:
                from grpc_reflection.v1alpha import reflection
                reflection.enable_server_reflection([...], server)
9. Architecture mismatch when installing grpc_php_plugin
	•	Problem:
            On M1 Mac (arm64), tried using grpc_php_plugin binary built for x86 →
                cannot execute binary file: Exec format error
    •	Fix:
        •	Compiled grpc_php_plugin manually in container.
        •	Eventually simplified: keep pre-generated stubs in repo (no need for plugin at runtime).

10. UI cleanup
	•	Problem:
Old PHP page had messy tables, double headers, warnings.
	•	Fix:
	•	Rewrote fac_gen.php with Tailwind-style CSS.
	•	Added record counts, pills, and responsive design.
	•	Now it cleanly renders student records from gRPC.

⸻

# Final Working Changes in php-app
	1.	Dockerfile fixes
	•	Installed nginx + php-fpm + composer + grpc/protobuf PHP extensions.
	•	Copied app, configs, composer.json, start.sh.
	•	Ran composer install.
	•	Copied php_client/ stubs into image.
	2.	Composer setup
	•	composer.json requires "grpc/grpc" and "google/protobuf".
	•	Autoload includes php_client/.
	3.	PHP frontend
	•	Uses gRPC client only (ListStudents or GetStudent).
	•	Includes generated stubs via php_client/.
	•	Clean UI.
	4.	Networking
	•	php-app connects to student-grpc:50051, not localhost.
	5.	Proto + service
	•	student.proto defines GetStudent and ListStudents.
	•	student_service.py implements both, with MySQL reconnect logic.
	6.	Debugging
	•	grpcurl installed to test endpoints.
	•	Reflection enabled.










