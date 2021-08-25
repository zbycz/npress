### Run npress in docker

1. `wget https://adminer.org/latest.php -O adminer.php`

2. Start both services with: `docker-compose up -d` or from IntelliJ IDE

3. Update `data/config.local.neon` with host: `docker_db_1`, dtb: `npress` and user/pass: `root`

4. Open http://localhost:8000

docker-compose down

### Debug:

```
docker-compose logs www
docker-compose exec db mysql -u root -p


docker ps
  CONTAINER ID   IMAGE
  61e37007b75b   docker_www
docker exec -it 61 /bin/bash
docker logs 61
```
