import bcrypt

# 1. Type your desired new password here
plain_password = b"adm"

# 2. Generate the salt and hash
#    (bcrypt handles the salt automatically inside the hash string)
hashed_password = bcrypt.hashpw(plain_password, bcrypt.gensalt())

# 3. Print the result
#    We decode it to a normal string so you can copy-paste it easily
print(hashed_password.decode('utf-8'))