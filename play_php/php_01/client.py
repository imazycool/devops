# client.py
import grpc
import student_pb2
import student_pb2_grpc

def run():
    channel = grpc.insecure_channel("localhost:50051")
    stub = student_pb2_grpc.StudentServiceStub(channel)

    response = stub.GetStudent(student_pb2.StudentRequest(id=1))
    print(f"🎓 Student -> ID: {response.id}, Name: {response.name}, Dept: {response.department}")

if __name__ == "__main__":
    run()