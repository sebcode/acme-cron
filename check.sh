openssl x509 -noout -modulus -in domain.crt | openssl md5
openssl rsa -noout -modulus -in domain.key | openssl md5
openssl req -noout -modulus -in domain.csr | openssl md5

