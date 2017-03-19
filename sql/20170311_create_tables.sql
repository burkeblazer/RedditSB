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

INSERT INTO "user" (name, auth_key, access_token, status) VALUES ('Test1', 'asdf', 'fdsa', true),('Test2', 'asdf', 'fdsa', true),('Test3', 'asdf', 'fdsa', true),('Test4', 'asdf', 'fdsa', true),('Test5', 'asdf', 'fdsa', true),('Test6', 'asdf', 'fdsa', true),('Test7', 'asdf', 'fdsa', true),('Test8', 'asdf', 'fdsa', true),('Test9', 'asdf', 'fdsa', true),('Test10', 'asdf', 'fdsa', true),('Test11', 'asdf', 'fdsa', true),('Test12', 'asdf', 'fdsa', true);

CREATE TABLE sport (
	sport_id serial PRIMARY KEY,
    "name" text NOT NULL
);

INSERT INTO sport ("name") VALUES ('Baseball'),('Basketball'),('Football'),('Hockey'),('Soccer'),('Tennis'),('Auto Racing'), ('Boxing'),('Cricket'),('Darts'),('eSports'),('Golf'),('Lacrosse'),('Martial Arts'),('Pool'),('Rugby'),('Snooker'),('Softball'),('Other');

CREATE TABLE bet_slip (
    bet_slip_id serial PRIMARY KEY,
    tags TEXT NOT NULL,
    public boolean NOT NULL DEFAULT TRUE,
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

CREATE TABLE user_tag (
    tag_id serial PRIMARY KEY,
    name TEXT NOT NULL,
    user_id integer REFERENCES "user",
    created timestamp NOT NULL DEFAULT now(),
    modified timestamp NOT NULL DEFAULT now(),
    status boolean NOT NULL DEFAULT TRUE
);

INSERT INTO user_tag ("name") VALUES ('Parlay'),('POTD'),('Baseball'),('Basketball'),('Football'),('Hockey'),('Soccer'),('Tennis'),('Auto Racing'), ('Boxing'),('Cricket'),('Darts'),('eSports'),('Golf'),('Lacrosse'),('Martial Arts'),('Pool'),('Rugby'),('Snooker'),('Softball'),('Money Line'),('Spread'),('Other');

CREATE TABLE user_following (
    user_following_id serial PRIMARY KEY,
    name TEXT NOT NULL,
    user_id integer REFERENCES "user" NOT NULL,
    following_user_id integer REFERENCES "user",
    created timestamp NOT NULL DEFAULT now(),
    modified timestamp NOT NULL DEFAULT now(),
    status boolean NOT NULL DEFAULT TRUE
);

CREATE TABLE bet_slip_like (
    bet_slip_like_id serial PRIMARY KEY,
    bet_slip_id integer REFERENCES bet_slip NOT NULL,
    user_id integer REFERENCES "user" NOT NULL,
    created timestamp NOT NULL DEFAULT now(),
    modified timestamp NOT NULL DEFAULT now(),
    status boolean NOT NULL DEFAULT TRUE
);

CREATE TABLE bet_slip_comment (
    bet_slip_comment_id serial PRIMARY KEY,
    "comment" TEXT NOT NULL,
    bet_slip_id integer REFERENCES bet_slip NOT NULL,
    user_id integer REFERENCES "user" NOT NULL,
    created timestamp NOT NULL DEFAULT now(),
    modified timestamp NOT NULL DEFAULT now(),
    status boolean NOT NULL DEFAULT TRUE
);