## Reporting security issues
If you think you have found a security-related issue affecting this software, please contact the author at devilshakerz@gmail.com (PGP: [DF3A 34D9 A627 42E5 BC6A 6750 1F2F B8AA 28FF E1BC](https://devilshakerz.com/pgp)) providing a detailed description and steps to reproduce the problem.

## Security considerations
- #### Old password hashes
It is recommended to use the plugin's algorithm wrapping feature to secure passwords saved using the MyBB 1.8's default algorithm.

 After all password hashes are secured, the forum administrators should take extra caution when handling database backups that contain passwords hashed with weak algorithms and, if possible, remove the old password data.

- #### Insecure algorithms
Webmasters should avoid using hashing algorithms or combinations thereof that are custom, less-popular or have not undergone an independent security audit.

- #### Encryption keys storage
The plugin needs to be able to access all encryption keys in use. The plugin allows to store them in the _inc/config.php_ file (less secure) and as an environment variable (recommended). Administrators should make sure that these values are not accessible from outside users or software.

 The encryption keys in use should be backed up in a safe location.

- #### Encryption key rotation
 The plugin allows hashes to be encrypted, decrypted and having their ciphertexts converted between encryption keys. In case of suspicion that some keys may have been accessed by a third party, a new default key should be generated (added at the end of the list / with the highest index) and hashes encrypted with previous keys should be converted to the new one.

- #### Password downgrades
The _Algorithm downgrades_ feature should only be used as a temporary measure for compatibility reasons. The downgraded passwords should be restored as soon as possible.
