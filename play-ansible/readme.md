# important steps 
1. install ansible 
2. generate keys 
3. move keys to safe folders 
4. configure ansible_ssh_private_keys in inventory file 
5. create user imazy in every container / vms and give eough privillages 
    - in cloud it can be configured while creating ec2 / vms 
6. ssh key generation 
    - run : ssh-keygen -t ed25519 -a 100 -C "ansible@org" 
    - move pvt key : mv /Users/ajay/.ssh/id_ed25519 keys/ansible_ed25519
    - move pbl key : mv /Users/ajay/.ssh/id_ed25519.pub keys/ansible_ed25519.pub 
    - chmod access : chmod 600 keys/ansible_ed25519

# ansible inventory file 
- very important and first file, where the target hosts are listed in structred and in logical grouped manner.
- we can also provide important variables like 
        - ansible_user 
        -  ansible_host 
        -  ansible_ssh_private_keys : <path>


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



# important notes 
-  group_vars 
    - standard directory where based on groups we can create directory exactly named like groupnames
    -  ansible will take the variable files automatically from group name directory 
    - ansible-valut create 