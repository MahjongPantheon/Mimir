CREATE TABLE "event_new"
(
  "id" integer PRIMARY KEY AUTOINCREMENT,
  "title" varchar(255) not null,
  "description" text not null,
  "start_time" timestamp,
  "end_time" timestamp,
  "game_duration" integer, -- for timer, duration in seconds
  "last_timer" integer, -- for timer, unix timestamp of last started timer
  "red_zone" integer, -- timer red zone amount in seconds, or null to disable red zone
  "owner_formation" integer, -- at least one owner id should be set!
  "owner_user" integer,
  "stat_host" varchar(255) not null, -- host of statistics frontend
  "sync_start" integer not null, -- should tables start synchronously or not (if not, players may start games when they want)
  "auto_seating" integer not null, -- enable automatic seating feature. Disabled if allow_player_append == true.
  "sort_by_games" integer not null, -- if true, players' rating table is sorted by games count first.
  "allow_player_append" integer not null, -- if true, new player may join event even if some games are already finished.
              -- Also, if true, games may be started only manually, and even when players count is not divisible by 4.
  "is_online" integer not null, -- if true, event is treated as online (paifu log parser is used). Disabled if is_textlog = true
  "is_textlog" integer not null, -- if true, non-interactive text log parser is used. For offline games.
  "type" varchar(255) not null, -- DEPRECATED: to be removed in 2.x! ; online or offline, tournament or local rating, interactive or simple
  "lobby_id" integer, -- tenhou lobby id for online events
  "ruleset" text not null, -- table rules, in JSON
  foreign key ("owner_formation") references "formation" ("id"),
  foreign key ("owner_user") references "user" ("id")
);

INSERT INTO "event_new" (
  "id", "title", "description", "start_time", "end_time", "game_duration",
  "last_timer", "red_zone", "owner_formation", "owner_user", "stat_host",
  "sync_start", "auto_seating", "sort_by_games", "allow_player_append",
  "is_online", "is_textlog", "type", "lobby_id", "ruleset"
)
  SELECT "id", "title", "description", "start_time", "end_time", "game_duration",
  "last_timer", "red_zone", "owner_formation", "owner_user", "stat_host",
  1, 1, 1, 0, 0, 0, "type", "lobby_id", "ruleset" FROM "event" WHERE "type" = 'offline_interactive_tournament';

INSERT INTO "event_new" (
  "id", "title", "description", "start_time", "end_time", "game_duration",
  "last_timer", "red_zone", "owner_formation", "owner_user", "stat_host",
  "sync_start", "auto_seating", "sort_by_games", "allow_player_append",
  "is_online", "is_textlog", "type", "lobby_id", "ruleset"
)
  SELECT "id", "title", "description", "start_time", "end_time", "game_duration",
  "last_timer", "red_zone", "owner_formation", "owner_user", "stat_host",
  0, 0, 0, 1, 0, 0, "type", "lobby_id", "ruleset" FROM "event" WHERE "type" = 'offline';

INSERT INTO "event_new" (
  "id", "title", "description", "start_time", "end_time", "game_duration",
  "last_timer", "red_zone", "owner_formation", "owner_user", "stat_host",
  "sync_start", "auto_seating", "sort_by_games", "allow_player_append",
  "is_online", "is_textlog", "type", "lobby_id", "ruleset"
)
  SELECT "id", "title", "description", "start_time", "end_time", "game_duration",
  "last_timer", "red_zone", "owner_formation", "owner_user", "stat_host",
  1, 1, 1, 0, 1, 0, "type", "lobby_id", "ruleset" FROM "event" WHERE "type" = 'online';

DROP TABLE IF EXISTS "event";
ALTER TABLE "event_new" RENAME TO "event";
CREATE INDEX "event_lobby" ON "event"("lobby_id");
