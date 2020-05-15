## Installation

Clone git repository

```
$ git clone https://github.com/goginyan/GitHubAPITask.git
```

Once this repository cloned into your machine, navigate to its root folder and run
```
$ composer install
```

## Installation with docker

Clone git repository
```
$ git clone https://github.com/goginyan/GitHubAPITask.git
```
Once this repository cloned into your machine, navigate to its root folder. Clone docker project here:
```
$ git clone https://github.com/goginyan/GitHubAPITask-Laradoc.git laradock
```
Navigate to new ``laradock`` folder
```
$ cd laradock
```
Create ``.env`` file from example
```
$ cp env-example .env
```
Run start script
```
$ ./start.sh
```
Execute workspace 
```
$ docker-compose exec workspace bash
```
Install Laravel vendor
```
$ composer install
```
## Usage

Project contains one endpoint: <strong>GET</strong>``/users`` with optional parameter ``{q}``


## License

Licensed under the [MIT license](https://opensource.org/licenses/MIT).
