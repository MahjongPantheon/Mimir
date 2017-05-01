ALTER TABLE event
  ADD "sync_start" integer null,
  ADD "auto_seating" integer null,
  ADD "sort_by_games" integer null,
  ADD "allow_player_append" integer null,
  ADD "is_online" integer null,
  ADD "is_textlog" integer null;

UPDATE event SET
  "sync_start" = 1,
  "auto_seating" = 1,
  "sort_by_games" = 1,
  "allow_player_append" = 0,
  "is_online" = 0,
  "is_textlog" = 0
  WHERE type = 'offline_interactive_tournament';

UPDATE event SET
  "sync_start" = 0,
  "auto_seating" = 0,
  "sort_by_games" = 0,
  "allow_player_append" = 1,
  "is_online" = 0,
  "is_textlog" = 0
  WHERE type = 'offline';

UPDATE event SET
  "sync_start" = 1,
  "auto_seating" = 1,
  "sort_by_games" = 1,
  "allow_player_append" = 0,
  "is_online" = 1,
  "is_textlog" = 0
  WHERE type = 'online';

ALTER TABLE event
  CHANGE "sync_start" "sync_start" integer not null,
  CHANGE "auto_seating" "auto_seating" integer not null,
  CHANGE "sort_by_games" "sort_by_games" integer not null,
  CHANGE "allow_player_append" "allow_player_append" integer not null,
  CHANGE "is_online" "is_online" integer not null,
  CHANGE "is_textlog" "is_textlog" integer not null;