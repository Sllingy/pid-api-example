PID Api Example
=================

This is small app demonstrating the usage of the PID API.

API Endpoints
------------

`/update` - Fetches and saves Points of Sale data from PID

`/list` - Returns Points of Sale data

`/list?day=2&time=13:52` - filters data by day and time. Uses current values if not specified

SQL
------------
```
CREATE DATABASE `points_of_sale_api` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
USE `points_of_sale_api`;

CREATE TABLE `opening_hours` (
  `id` int NOT NULL,
  `point_of_sale_id` varchar(255) NOT NULL,
  `day_from` int NOT NULL,
  `day_to` int NOT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `points_of_sale` (
  `id` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `lat` float NOT NULL,
  `lon` float NOT NULL,
  `services` int NOT NULL,
  `pay_methods` int NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

ALTER TABLE `opening_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `point_of_sale_id` (`point_of_sale_id`);

ALTER TABLE `points_of_sale`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `opening_hours`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `opening_hours`
  ADD CONSTRAINT `opening_hours_ibfk_1` FOREIGN KEY (`point_of_sale_id`) REFERENCES `points_of_sale` (`id`);
```