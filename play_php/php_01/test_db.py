# test_db.py
import mysql.connector
import time

# MySQL connection parameters
DB_HOST = "mariadb"          # Container hostname
DB_PORT = 3306
DB_USER = "student_user"     # Non-root user
DB_PASSWORD = "student_pass"
DB_NAME = "student"

retries = 10

for attempt in range(retries):
    try:
        conn = mysql.connector.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USER,
            password=DB_PASSWORD,
            database=DB_NAME
        )
        print("✅ Connected to MySQL as student_user!")
        conn.close()
        break
    except mysql.connector.Error as e:
        print(f"⏳ Attempt {attempt + 1}/{retries}: Cannot connect to MySQL ({e})")
        time.sleep(3)
else:
    raise Exception(f"❌ Unable to connect to MySQL at {DB_HOST}:{DB_PORT} after {retries} retries")