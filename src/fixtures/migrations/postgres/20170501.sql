ALTER TABLE event
  ADD "sync_start" integer null,
  ADD "auto_seating" integer null,
  ADD "sort_by_games" integer null,
  ADD "allow_player_append" integer null,
  ADD "use_timer" integer null,
  ADD "is_online" integer null,
  ADD "is_textlog" integer null;

UPDATE event SET
  "sync_start" = 1,
  "auto_seating" = 1,
  "sort_by_games" = 1,
  "allow_player_append" = 0,
  "use_timer" = 1,
  "is_online" = 0,
  "is_textlog" = 0
  WHERE type = 'offline_interactive_tournament';

UPDATE event SET
  "sync_start" = 0,
  "auto_seating" = 0,
  "sort_by_games" = 0,
  "allow_player_append" = 1,
  "use_timer" = 0,
  "is_online" = 0,
  "is_textlog" = 0
  WHERE type = 'offline';

UPDATE event SET
  "sync_start" = 1,
  "auto_seating" = 1,
  "sort_by_games" = 1,
  "allow_player_append" = 0,
  "use_timer" = 0,
  "is_online" = 1,
  "is_textlog" = 0
  WHERE type = 'online';

ALTER TABLE event
  ALTER COLUMN "sync_start" TYPE integer not null,
  ALTER COLUMN "auto_seating" TYPE integer not null,
  ALTER COLUMN "sort_by_games" TYPE integer not null,
  ALTER COLUMN "allow_player_append" TYPE integer not null,
  ALTER COLUMN "use_timer" TYPE integer not null,
  ALTER COLUMN "is_online" TYPE integer not null,
  ALTER COLUMN "is_textlog" TYPE integer not null;
