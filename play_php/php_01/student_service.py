# student_service.py
import time
from concurrent import futures

import grpc
from grpc_reflection.v1alpha import reflection

import mysql.connector
from mysql.connector import Error
from mysql.connector.pooling import MySQLConnectionPool

import student_pb2
import student_pb2_grpc
from google.protobuf import empty_pb2  # for ListStudents()

DB_CONFIG = dict(
    host="mariadb",
    user="student_user",
    password="student_pass",
    database="student",
    port=3306,
    autocommit=True,
)

POOL_NAME = "student_pool"
POOL_SIZE = 10


def wait_for_db(max_retries=30, delay=3):
    """Block until DB is reachable (one-off check at startup)."""
    for attempt in range(1, max_retries + 1):
        try:
            conn = mysql.connector.connect(**DB_CONFIG)
            conn.close()
            print("✅ Connected to MySQL!", flush=True)
            return
        except Error as e:
            print(f"⏳ Waiting for MySQL... try {attempt}/{max_retries} ({e})", flush=True)
            time.sleep(delay)
    raise RuntimeError("❌ Unable to connect to MySQL after retries")


class StudentService(student_pb2_grpc.StudentServiceServicer):
    def __init__(self, pool: MySQLConnectionPool):
        self.pool = pool

    def _conn_cursor(self):
        """Get pooled connection + cursor; caller must close both."""
        conn = self.pool.get_connection()
        cur = conn.cursor()
        return conn, cur

    def GetStudent(self, request, context):
        conn, cur = self._conn_cursor()
        try:
            cur.execute(
                "SELECT id, name, standard FROM admission WHERE id = %s",
                (request.id,),
            )
            row = cur.fetchone()
            if not row:
                context.set_code(grpc.StatusCode.NOT_FOUND)
                context.set_details("Student not found")
                return student_pb2.StudentResponse()
            student = student_pb2.Student(id=row[0], name=row[1], department=row[2])
            return student_pb2.StudentResponse(student=student)
        finally:
            try:
                cur.close()
            finally:
                conn.close()  # returns to pool

    def ListStudents(self, request: empty_pb2.Empty, context):
        conn, cur = self._conn_cursor()
        try:
            cur.execute("SELECT id, name, standard FROM admission")
            rows = cur.fetchall()
            students = [
                student_pb2.Student(id=r[0], name=r[1], department=r[2]) for r in rows
            ]
            return student_pb2.StudentListResponse(students=students)
        finally:
            try:
                cur.close()
            finally:
                conn.close()  # returns to pool


def serve():
    # 1) wait until DB is reachable once
    wait_for_db()

    # 2) build a pool for thread-safe access under gRPC's ThreadPool
    pool = MySQLConnectionPool(pool_name=POOL_NAME, pool_size=POOL_SIZE, **DB_CONFIG)

    # 3) start gRPC
    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    student_pb2_grpc.add_StudentServiceServicer_to_server(StudentService(pool), server)

    # 4) enable reflection so grpcurl can discover methods
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