drop table files;
drop table software;
drop table attacks;
CREATE TABLE attacks(
	id INT AUTO_INCREMENT,
	name TEXT NOT NULL,
	description TEXT NOT NULL,
	so TINYTEXT NOT NULL,
	attack_action TEXT NOT NULL,

	CONSTRAINT pk_attacks PRIMARY KEY(id),
	CONSTRAINT ck_attack_action CHECK(attack_action = 'software' OR attack_action = 'file'),
	CONSTRAINT ck_so CHECK(so='linux' OR so='winxp')
);
CREATE TABLE files(
	id INT AUTO_INCREMENT,
	file_path TEXT NOT NULL,
	string TEXT NOT NULL,
	quantity INT NOT NULL,
	attack_id INT NOT NULL,
	CONSTRAINT pk_files PRIMARY KEY(id),
    CONSTRAINT fk_attack_id FOREIGN KEY(attack_id) REFERENCES attacks(id),
    CONSTRAINT ck_quantity CHECK(quantity>0)
);
CREATE TABLE software(
	id INT AUTO_INCREMENT,
	file_type TEXT NOT NULL,
	file_name TEXT NOT NULL,
	file_size BIGINT NOT NULL,
	bin_data BLOB NOT NULL,
	attack_id INT NOT NULL,
	CONSTRAINT pk_software PRIMARY KEY(id),
    CONSTRAINT fk_attack_id2 FOREIGN KEY(attack_id) REFERENCES attacks(id)
);


INSERT INTO attacks(name, description, so, attack_action) VALUES('Login brute force', 'Tentar autentificar, usando brute force', 'linux', 'file');
INSERT INTO files(file_path, string, quantity, attack_id) VALUES('/var/log/vsftpd.log', 'Sat Mar 21 08:01:31 2015 [pid 14802] [msfadmin] FAIL LOGIN: Client \\"192.168.206.129\\"', 5, 1);

INSERT INTO attacks(name, description, so, attack_action) VALUES('Login brute force', 'Tentar autentificar, usando brute force', 'windows', 'file');
INSERT INTO files(file_path, string, quantity, attack_id) VALUES('/var/log/vsftpd.log', 'Sat Mar 21 08:01:31 2015 [pid 14802] [msfadmin] FAIL LOGIN: Client \\"192.168.206.129\\"', 10, 2);
INSERT INTO files(file_path, string,quantity, attack_id) VALUES('/var/log/vsftpd.log', 'Sat Mar 21 08:01:31 2015 [pid 14802] [msfadmin] FAIL LOGIN: Client \\"192.168.206.129\\"', 20, 2);
