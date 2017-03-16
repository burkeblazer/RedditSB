CREATE TABLE "user" (
	user_id serial PRIMARY KEY, 
	name TEXT NOT NULL,
	auth_key TEXT, 
	access_token TEXT,
	session_id TEXT, 
	modified timestamp default now(), 
	created timestamp default now(), 
	status boolean NOT NULL
);

CREATE TABLE sport (
	sport_id serial PRIMARY KEY,
    "name" text NOT NULL
);

INSERT INTO sport ("name") VALUES ('Baseball'),('Basketball'),('Football'),('Hockey'),('Soccer'),('Tennis'),('Auto Racing'), ('Boxing'),('Cricket'),('Darts'),('eSports'),('Golf'),('Lacrosse'),('Martial Arts'),('Pool'),('Rugby'),('Snooker'),('Softball'),('Other');

CREATE TABLE bet_slip (
    bet_slip_id serial PRIMARY KEY,
    name TEXT NOT NULL,
    public boolean NOT NULL DEFAULT TRUE,
    notes TEXT,
    slip_date date NOT NULL DEFAULT now(),
    user_id integer REFERENCES "user" NOT NULL,
    created timestamp NOT NULL DEFAULT now(),
    modified timestamp NOT NULL DEFAULT now(),
    status boolean NOT NULL DEFAULT TRUE
);

CREATE TABLE bet (
    bet_id serial PRIMARY KEY,
    matches TEXT NOT NULL,
    units_bet float NOT NULL,
    units_to_win float NOT NULL,
    outcome TEXT NOT NULL DEFAULT 'TBD',
    bet_slip_id integer REFERENCES bet_slip NOT NULL,
    created timestamp NOT NULL DEFAULT now(),
    modified timestamp NOT NULL DEFAULT now(),
    status boolean NOT NULL DEFAULT TRUE
);