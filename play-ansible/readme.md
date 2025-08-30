# important steps 
1. install ansible 
2. generate keys 
3. move keys to safe folders 
4. configure ansible_ssh_private_keys in inventory file 
5. create user imazy in every container / vms and give eough privillages 
    - in cloud it can be configured while creating ec2 / vms 
6. ssh key generation 
    - run : ssh-keygen -t ed25519 -a 100 -C "ansible@org" 
    - move pvt key : cp /Users/ajay/.ssh/id_ed25519 keys/ansible_ed25519
    - move pbl key : cp /Users/ajay/.ssh/id_ed25519.pub keys/ansible_ed25519.pub 
    - chmod access : chmod 600 keys/ansible_ed25519

--- 
# ansible inventory file 
    - very important and first file, where the target hosts are listed in structred and in logical grouped manner.
    - we can also provide important variables like 
            - ansible_user 
            - ansible_host 
            - ansible_ssh_private_keys : <path>


--- 
# types of variables 
1. playbook variables --> defined inside playbook, scope within playbook.yaml 
2. ansible_variables --> ansible configuration variables, ansible_user, .cfg variables 
3. inventory file based variables --> inventory file has definition of <host_01>, <group_01>
    - these can be defined in two major directory 1). group_vars 2). host_vars 
    - group_vars directory will have subdirectory for <group_01> and *<all>* for all hosts defined inside inventory file.
    - host_vars directory will have --> <host_01> subdirectory to store their respective variables. 
4. fact varible --> derrived from setup module 
    - gathering_facts is default module runs to check the existing status of remote/ target machine 
    - ansible_os_family/ ansible_default_ipv4 
5. register variables --> storing ouput in variable , run time variable 
    - so it can be called and use for decision making / debug purpose 
    - debug is used to print the variable, debug has two options 
        - var : <variable_name> , just call the name of var and it will be printed
        - msg : " some texts {{<variable_name>}} " --> formating of variable or static msg can be printed 
        

---
# important commands 

1. ansible myhosts  -m ping -i inv/my_inv.yaml   
    - to get all configured hosts 

2. ansible-inventory -i inv/my_inv.yaml --list
    -  to get all inventory details 

3. ansible-vault create group_vars/all/vault.yaml 
    -  group_vars/host_vars --> is standard name of the directory 
    - under group_vars, directory names should be created exactly same name as group names.
    - all group named sub directory can have variable name declared files 

4. how to check which arguments are required and which are not 
    - ansible-doc community.mysql.mysql_user 
    - ansible-doc command + module name => will show all the listed arguments and details 

5. create config file with all possible arguments 
    - ansible-config init --disable > ansible.cfg 


--- 
# important notes 
-  group_vars 
    - standard directory where based on groups we can create directory exactly named like groupnames
    - ansible will take the variable files automatically from group name directory 
    - ansible-valut create 

- key arguments and structure of ansible-playbook 
    - name : name of the configuration 
    - hosts : name of the group as per inventory files 
    - become : privillage usage arguments --> true / false 
    - vars : all variables used for this playbook globally 
    - tasks
        - name : name of the task -01 
        - module name : yum/ apt/ service/ pip/ community.mysql.mysql_user/ community.mysql.mysql_db 
            - required arguments : name=httpd, state=present/ absent/ started, user=my_user 
            - optional arguments : ignore_errors, enabled: true
            - register argument : <variable> --> to store output of the tasks in defined variable_name.

- key arguments and structure in defining inventory file 
    - the engine of the ansible 
    - contains definition of all target machines and access details, we can create a logical group of machines  
        - all:
            - vars: 
                - ansible_user:
                - ansible_ssh_private_key_file: path of public and pvt keys 
                - ansible_become_method: sudo 
            - hosts: 
                - <host_01> : 
                    ansible_host: <host_01_ip> / <host_01_name>
                - <host_02> :
                    ansible_host: <host_02_ip> / <host_02_name>
            - children: 
                - <group_01> :
                    - hosts:
                        host_01
                        host_03
                - <group_02> :
                    - hosts: 
                        host_02
                        host_05

 


