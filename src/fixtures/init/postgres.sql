-- Players, orgs, etc
DROP TABLE IF EXISTS "user";
CREATE TABLE "user"
(
  "id" serial,
  primary key ("id"),
  "ident" varchar(255) not null, -- oauth ident info, for example
  "alias" varchar(255), -- user alias for text-mode game log
  "display_name" varchar(255) not null,
  "city" varchar(255),
  "tenhou_id" varchar(255)
);
CREATE INDEX "user_alias" ON "user" ("alias");
CREATE UNIQUE INDEX "user_ident" ON "user" ("ident");
CREATE INDEX "user_tenhou" ON "user" ("tenhou_id");

-- Local clubs, leagues, etc
DROP TABLE IF EXISTS "formation";
CREATE TABLE "formation"
(
  "id" serial,
  primary key ("id"),
  "title" varchar(255) not null,
  "city" varchar(255) not null,
  "description" text not null,
  "logo" text,
  "contact_info" text not null,
  "primary_owner" integer not null,
  foreign key ("primary_owner") references "user" ("id")
);

-- Many-to-many relation, primarily for administrative needs. By default user is a player in formation.
DROP TABLE IF EXISTS "formation_user";
CREATE TABLE "formation_user"
(
  "formation_id" integer not null,
  "user_id" integer not null,
  "role" varchar(255) not null, -- who is this user in this group?
  foreign key ("formation_id") references "formation" ("id"),
  foreign key ("user_id") references "user" ("id")
);

-- Local ratings, tournaments, including online ones
DROP TABLE IF EXISTS "event";
CREATE TABLE "event"
(
  "id" serial,
  primary key ("id"),
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
  "use_timer" integer not null, -- if true, timer is shown in mobile app, also timer page is available in administration tools
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
CREATE INDEX "event_lobby" ON "event"("lobby_id");

-- Users registered in event
DROP TABLE IF EXISTS "event_registered_users";
CREATE TABLE "event_registered_users"
(
  "id" serial,
  primary key ("id"),
  "event_id" integer not null,
  "user_id" integer not null,
  "auth_token" varchar(48),
  foreign key ("event_id") references "event" ("id"),
  foreign key ("user_id") references "user" ("id")
);
-- Unique index name should be TABLENAME_uniq to make sure postgres driver finds it.
CREATE UNIQUE INDEX "event_registered_users_uniq" ON "event_registered_users"("event_id","user_id");
CREATE INDEX "eru_auth_token" ON "event_registered_users"("auth_token");

-- Users to be registered in event (tournament-type auth)
DROP TABLE IF EXISTS "event_enrolled_users";
CREATE TABLE "event_enrolled_users"
(
  "id" serial,
  primary key ("id"),
  "event_id" integer not null,
  "user_id" integer not null,
  "reg_pin" integer,
  foreign key ("event_id") references "event" ("id"),
  foreign key ("user_id") references "user" ("id")
);
-- Unique index name should be TABLENAME_uniq to make sure postgres driver finds it.
CREATE UNIQUE INDEX "event_enrolled_users_uniq" ON "event_enrolled_users"("event_id","user_id");
CREATE INDEX "eeu_pin" ON "event_enrolled_users"("reg_pin");

-- Game session: tonpuusen, hanchan, either online or offline
DROP TABLE IF EXISTS "session";
CREATE TABLE "session"
(
  "id" serial,
  primary key ("id"),
  "event_id" integer not null,
  "representational_hash" varchar(255), -- hash to find this game from client mobile app
  "replay_hash" varchar(255), -- tenhou game hash, for deduplication
  "table_index" integer, -- table number in tournament
  "orig_link" text, -- original tenhou game link, for access to replay
  "start_date" timestamp,
  "end_date" timestamp,
  "status" varchar(255), -- planned / inprogress / finished
  "intermediate_results" text, -- json-encoded results for in-progress sessions
  foreign key ("event_id") references "event" ("id")
);
CREATE INDEX "session_replay" ON "session"("replay_hash");
CREATE INDEX "session_status" ON "session"("status");
CREATE INDEX "session_table_index" ON "session"("table_index");
CREATE INDEX "session_rephash" ON "session"("representational_hash");

-- Many-to-many relation
DROP TABLE IF EXISTS "session_user";
CREATE TABLE "session_user"
(
  "session_id" integer not null,
  "user_id" integer not null,
  "order" integer not null, -- position in game
  foreign key ("session_id") references "session" ("id"),
  foreign key ("user_id") references "user" ("id")
);
-- Unique index name should be TABLENAME_uniq to make sure postgres driver finds it.
CREATE UNIQUE INDEX "session_user_uniq" ON "session_user"("session_id","user_id");

-- Session results, entry should exist only for finished sessions
DROP TABLE IF EXISTS "session_results";
CREATE TABLE "session_results"
(
  "id" serial,
  primary key ("id"),
  "event_id" integer not null,
  "session_id" integer not null,
  "player_id" integer not null,
  "score" integer not null, -- how many points player has at the end, before any uma/oka calc
  "rating_delta" float not null, -- resulting score after uma/oka and starting points subtraction
  "place" integer not null,
  foreign key ("event_id") references "event" ("id"),
  foreign key ("session_id") references "session" ("id"),
  foreign key ("player_id") references "user" ("id")
);

-- Session round results
DROP TABLE IF EXISTS "round";
CREATE TABLE "round"
(
  "id" serial,
  primary key ("id"),
  "session_id" integer not null,
  "event_id" integer not null,
  "outcome" varchar(255) not null, -- ron, tsumo, draw, abortive draw or chombo
  "winner_id" integer, -- not null only on ron or tsumo
  "loser_id" integer, -- not null only on ron or chombo
  "han" integer,
  "fu" integer,
  "round" integer not null, -- 1-4 means east1-4, 5-8 means south1-4, etc
  "tempai" varchar(255), -- comma-separated list of tempai user ids
  "yaku" varchar(255), -- comma-separated yaku id list
  "dora" integer, -- dora count
  "uradora" integer, -- TODO: not sure if we really need these guys
  "kandora" integer,
  "kanuradora" integer,
  "riichi" varchar(255), -- comma-separated list of user ids who called riichi
  "multi_ron" integer, -- double or triple ron flag to properly display results of round
  "last_session_state" text, -- session intermediate results before this round was registered
  "open_hand" integer, -- boolean, was winner's hand opened or not
  foreign key ("session_id") references "session" ("id"),
  foreign key ("event_id") references "event" ("id"),
  foreign key ("winner_id") references "user" ("id"),
  foreign key ("loser_id") references "user" ("id")
);
CREATE INDEX "round_outcome" ON "round"("outcome");

-- User rating history in context of every event
DROP TABLE IF EXISTS "player_history";
CREATE TABLE "player_history"
(
  "id" serial,
  primary key ("id"),
  "user_id" integer not null,
  "session_id" integer not null,
  "event_id" integer not null,
  "rating" float not null,
  "avg_place" float not null,
  "games_played" integer not null,
  foreign key ("user_id") references "user" ("id"),
  foreign key ("session_id") references "session" ("id"),
  foreign key ("event_id") references "event" ("id")
);
