# LockGuardCommand

## Description
This is an abstract class for Symfony commands that provides functionality for creating and managing lock files. When you extend this class and run your command, a lock file will be automatically created in the var/log/lock/ directory. The lock file will have the same name as your command, but with a .lck extension. The purpose of the lock file is to prevent multiple instances of the same command from running simultaneously. Once your command is done running, the lock file will be deleted automatically.
