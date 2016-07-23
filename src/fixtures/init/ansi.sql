-- README:
-- Commented out "IF EXISTS" clauses are expected to be uncommented for every supporting DB
-- Commented out "serial" clauses indicate fields that should be serial/auto_increment.
-- Corresponding modifications to resulting DB-specific dumps are expected.

-- Players, orgs, etc
DROP TABLE
-- IF EXISTS
   "user";
CREATE TABLE "user" (
  "id" integer, -- serial
  primary key ("id"),
  "ident" varchar(255) not null, -- oauth ident info, for example
  "display_name" varchar(255) not null,
  "tenhou_id" varchar(255)
);
CREATE INDEX "user_ident" ON "user" ("ident");
CREATE INDEX "user_tenhou" ON "user" ("tenhou_id");

-- Local clubs, leagues, etc
DROP TABLE
-- IF EXISTS
   "formation";
CREATE TABLE "formation" (
  "id" integer, -- serial
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
DROP TABLE
-- IF EXISTS
   "formation_user";
CREATE TABLE "formation_user" (
  "formation_id" integer not null,
  "user_id" integer not null,
  "role" varchar(255) not null, -- who is this user in this group?
  foreign key ("formation_id") references "formation" ("id"),
  foreign key ("user_id") references "user" ("id")
);

-- Local ratings, tournaments, including online ones
DROP TABLE
-- IF EXISTS
   "event";
CREATE TABLE "event" (
  "id" integer, -- serial
  primary key ("id"),
  "title" varchar(255) not null,
  "description" text not null,
  "start_time" timestamp,
  "end_time" timestamp,
  "owner_formation" integer, -- at least one owner id should be set!
  "owner_user" integer,
  "type" varchar(255) not null, -- online or offline, tournament or local rating, hiroshima or normal
  "lobby_id" integer, -- tenhou lobby id for online events
  "ruleset" text not null, -- table rules, in JSON
  foreign key ("owner_formation") references "formation" ("id"),
  foreign key ("owner_user") references "user" ("id")
);
CREATE INDEX "event_lobby" ON "event"("lobby_id");

-- Game session: tonpuusen, hanchan, either online or offline
DROP TABLE
-- IF EXISTS
   "session";
CREATE TABLE "session" (
  "id" integer, -- serial
  primary key ("id"),
  "event_id" integer not null,
  "replay_hash" varchar(255), -- tenhou game hash, for deduplication
  "orig_link" text, -- original tenhou game link, for access to replay
  "play_date" timestamp,
  "players" varchar(255), -- comma-separated ordered list of player ids, east to north.
  "state" varchar(255), -- planned / in progress / finished
  foreign key ("event_id") references "event" ("id")
);
CREATE INDEX "session_replay" ON "session"("replay_hash");
CREATE INDEX "session_state" ON "session"("state");

-- Session results, entry should exist only for finished sessions
DROP TABLE
-- IF EXISTS
   "session_results";
CREATE TABLE "session_results" (
  "event_id" integer not null,
  "session_id" integer not null,
  "user_id" integer not null,
  "score" integer not null, -- how many points player has at the end, before any uma/oka calc
  "result_score" float not null, -- resulting score after uma/oka and starting points subtraction
  "place" integer not null,
  foreign key ("event_id") references "event" ("id"),
  foreign key ("session_id") references "session" ("id"),
  foreign key ("user_id") references "user" ("id")
);

-- Session round results
DROP TABLE
-- IF EXISTS
   "round";
CREATE TABLE "round" (
  "id" integer, -- serial
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
  foreign key ("session_id") references "session" ("id"),
  foreign key ("event_id") references "event" ("id"),
  foreign key ("winner_id") references "user" ("id"),
  foreign key ("loser_id") references "user" ("id")
);
CREATE INDEX "round_outcome" ON "round"("outcome");

-- User rating history in context of every event
DROP TABLE
-- IF EXISTS
   "user_history";
CREATE TABLE "user_history" (
  "id" integer, -- serial
  primary key ("id"),
  "user_id" integer not null,
  "session_id" integer not null,
  "event_id" integer not null,
  "rating" float not null,
  foreign key ("user_id") references "user" ("id"),
  foreign key ("session_id") references "session" ("id"),
  foreign key ("event_id") references "event" ("id")
);

-- User stats
DROP TABLE
-- IF EXISTS
   "user_stats";
CREATE TABLE "user_stats" (
  "id" integer, -- serial
  primary key ("id"),
  "user_id" integer not null,
  "period" integer, -- Aggregation period for stats (in days).
  "stats" text, -- JSON of many stats: places distribution, count of wins and loses, etc
  foreign key ("user_id") references "user" ("id")
);