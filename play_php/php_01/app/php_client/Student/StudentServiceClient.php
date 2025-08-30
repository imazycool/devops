<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Student;

/**
 */
class StudentServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Student\StudentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Student\StudentResponse>
     */
    public function GetStudent(\Student\StudentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/Student.StudentService/GetStudent',
        $argument,
        ['\Student\StudentResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Google\Protobuf\GPBEmpty $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Student\StudentListResponse>
     */
    public function ListStudents(\Google\Protobuf\GPBEmpty $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/Student.StudentService/ListStudents',
        $argument,
        ['\Student\StudentListResponse', 'decode'],
        $metadata, $options);
    }

}
