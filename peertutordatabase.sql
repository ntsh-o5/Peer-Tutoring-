SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 240 (class 1259 OID 16813)
-- Name: academic_progress; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.academic_progress (
    id integer NOT NULL,
    learner_id integer NOT NULL,
    unit_code character varying(20) NOT NULL,
    grade_before character varying(10) NOT NULL,
    grade_after character varying(10) DEFAULT NULL::character varying,
    proof_before character varying(255) NOT NULL,
    proof_after character varying(255) DEFAULT NULL::character varying,
    recorded_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.academic_progress OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 16812)
-- Name: academic_progress_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.academic_progress_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.academic_progress_id_seq OWNER TO postgres;

--
-- TOC entry 5156 (class 0 OID 0)
-- Dependencies: 239
-- Name: academic_progress_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.academic_progress_id_seq OWNED BY public.academic_progress.id;


--
-- TOC entry 222 (class 1259 OID 16494)
-- Name: bookings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bookings (
    id integer NOT NULL,
    learner_id integer,
    tutor_id integer,
    unit_code character varying(100) NOT NULL,
    booking_date timestamp without time zone NOT NULL,
    status character varying(50) DEFAULT 'scheduled'::character varying NOT NULL
);


ALTER TABLE public.bookings OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 16493)
-- Name: bookings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.bookings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bookings_id_seq OWNER TO postgres;

--
-- TOC entry 5157 (class 0 OID 0)
-- Dependencies: 221
-- Name: bookings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.bookings_id_seq OWNED BY public.bookings.id;


--
-- TOC entry 226 (class 1259 OID 16541)
-- Name: feedback; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.feedback (
    id integer NOT NULL,
    booking_id integer,
    learner_id integer,
    tutor_id integer,
    comments text NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.feedback OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 16540)
-- Name: feedback_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.feedback_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.feedback_id_seq OWNER TO postgres;

--
-- TOC entry 5158 (class 0 OID 0)
-- Dependencies: 225
-- Name: feedback_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.feedback_id_seq OWNED BY public.feedback.id;


--
-- TOC entry 236 (class 1259 OID 16766)
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
    notification_id integer NOT NULL,
    user_id integer NOT NULL,
    booking_id integer,
    title character varying(150) NOT NULL,
    message text NOT NULL,
    is_read boolean DEFAULT false,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.notifications OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 16765)
-- Name: notifications_notification_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notifications_notification_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notifications_notification_id_seq OWNER TO postgres;

--
-- TOC entry 5159 (class 0 OID 0)
-- Dependencies: 235
-- Name: notifications_notification_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notifications_notification_id_seq OWNED BY public.notifications.notification_id;


--
-- TOC entry 238 (class 1259 OID 16791)
-- Name: payments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payments (
    payment_id integer NOT NULL,
    user_id integer NOT NULL,
    amount numeric(10,2) DEFAULT 500.00 NOT NULL,
    transaction_ref character varying(100) DEFAULT NULL::character varying,
    status character varying(30) DEFAULT 'Pending'::character varying,
    paid_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.payments OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 16790)
-- Name: payments_payment_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payments_payment_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payments_payment_id_seq OWNER TO postgres;

--
-- TOC entry 5160 (class 0 OID 0)
-- Dependencies: 237
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payments_payment_id_seq OWNED BY public.payments.payment_id;


--
-- TOC entry 228 (class 1259 OID 16568)
-- Name: progress_reports; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.progress_reports (
    id integer NOT NULL,
    booking_id integer,
    tutor_id integer,
    learner_id integer,
    performance_assessment character varying(255) NOT NULL,
    academic_remarks text NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    grade_achieved character varying(10)
);


ALTER TABLE public.progress_reports OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 16567)
-- Name: progress_reports_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.progress_reports_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.progress_reports_id_seq OWNER TO postgres;

--
-- TOC entry 5161 (class 0 OID 0)
-- Dependencies: 227
-- Name: progress_reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.progress_reports_id_seq OWNED BY public.progress_reports.id;


--
-- TOC entry 224 (class 1259 OID 16516)
-- Name: ratings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ratings (
    id integer NOT NULL,
    booking_id integer,
    learner_id integer,
    tutor_id integer,
    rating integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ratings_rating_check CHECK (((rating >= 1) AND (rating <= 5)))
);


ALTER TABLE public.ratings OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 16515)
-- Name: ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ratings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ratings_id_seq OWNER TO postgres;

--
-- TOC entry 5162 (class 0 OID 0)
-- Dependencies: 223
-- Name: ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ratings_id_seq OWNED BY public.ratings.id;


--
-- TOC entry 234 (class 1259 OID 16687)
-- Name: tutor_availability; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tutor_availability (
    availability_id integer NOT NULL,
    tutor_id integer NOT NULL,
    day_of_week character varying(15) NOT NULL,
    start_time time without time zone NOT NULL,
    end_time time without time zone NOT NULL
);


ALTER TABLE public.tutor_availability OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 16686)
-- Name: tutor_availability_availability_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tutor_availability_availability_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tutor_availability_availability_id_seq OWNER TO postgres;

--
-- TOC entry 5163 (class 0 OID 0)
-- Dependencies: 233
-- Name: tutor_availability_availability_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tutor_availability_availability_id_seq OWNED BY public.tutor_availability.availability_id;


--
-- TOC entry 232 (class 1259 OID 16669)
-- Name: tutor_credentials; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tutor_credentials (
    credential_id integer NOT NULL,
    tutor_id integer NOT NULL,
    unit_code character varying(20) NOT NULL,
    transcript_path character varying(255) NOT NULL,
    submission_status character varying(20) DEFAULT 'pending'::character varying,
    submitted_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.tutor_credentials OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 16668)
-- Name: tutor_credentials_credential_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tutor_credentials_credential_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tutor_credentials_credential_id_seq OWNER TO postgres;

--
-- TOC entry 5164 (class 0 OID 0)
-- Dependencies: 231
-- Name: tutor_credentials_credential_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tutor_credentials_credential_id_seq OWNED BY public.tutor_credentials.credential_id;


--
-- TOC entry 230 (class 1259 OID 16619)
-- Name: tutor_units; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tutor_units (
    unit_id integer NOT NULL,
    tutor_id integer,
    unit_name character varying(100)
);


ALTER TABLE public.tutor_units OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 16618)
-- Name: tutor_units_unit_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tutor_units_unit_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tutor_units_unit_id_seq OWNER TO postgres;

--
-- TOC entry 5165 (class 0 OID 0)
-- Dependencies: 229
-- Name: tutor_units_unit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tutor_units_unit_id_seq OWNED BY public.tutor_units.unit_id;


--
-- TOC entry 220 (class 1259 OID 16388)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    password_hash character varying(255) NOT NULL,
    role character varying(20) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    status character varying(20) DEFAULT 'active'::character varying,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'learner'::character varying, 'tutor'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 16387)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- TOC entry 5166 (class 0 OID 0)
-- Dependencies: 219
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 4930 (class 2604 OID 16816)
-- Name: academic_progress id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.academic_progress ALTER COLUMN id SET DEFAULT nextval('public.academic_progress_id_seq'::regclass);


--
-- TOC entry 4909 (class 2604 OID 16497)
-- Name: bookings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings ALTER COLUMN id SET DEFAULT nextval('public.bookings_id_seq'::regclass);


--
-- TOC entry 4913 (class 2604 OID 16544)
-- Name: feedback id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.feedback ALTER COLUMN id SET DEFAULT nextval('public.feedback_id_seq'::regclass);


--
-- TOC entry 4922 (class 2604 OID 16769)
-- Name: notifications notification_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications ALTER COLUMN notification_id SET DEFAULT nextval('public.notifications_notification_id_seq'::regclass);


--
-- TOC entry 4925 (class 2604 OID 16794)
-- Name: payments payment_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments ALTER COLUMN payment_id SET DEFAULT nextval('public.payments_payment_id_seq'::regclass);


--
-- TOC entry 4915 (class 2604 OID 16571)
-- Name: progress_reports id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progress_reports ALTER COLUMN id SET DEFAULT nextval('public.progress_reports_id_seq'::regclass);


--
-- TOC entry 4911 (class 2604 OID 16519)
-- Name: ratings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ratings ALTER COLUMN id SET DEFAULT nextval('public.ratings_id_seq'::regclass);


--
-- TOC entry 4921 (class 2604 OID 16690)
-- Name: tutor_availability availability_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_availability ALTER COLUMN availability_id SET DEFAULT nextval('public.tutor_availability_availability_id_seq'::regclass);


--
-- TOC entry 4918 (class 2604 OID 16672)
-- Name: tutor_credentials credential_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_credentials ALTER COLUMN credential_id SET DEFAULT nextval('public.tutor_credentials_credential_id_seq'::regclass);


--
-- TOC entry 4917 (class 2604 OID 16622)
-- Name: tutor_units unit_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_units ALTER COLUMN unit_id SET DEFAULT nextval('public.tutor_units_unit_id_seq'::regclass);


--
-- TOC entry 4906 (class 2604 OID 16391)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 5150 (class 0 OID 16813)
-- Dependencies: 240
-- Data for Name: academic_progress; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.academic_progress (id, learner_id, unit_code, grade_before, grade_after, proof_before, proof_after, recorded_at) FROM stdin;
1	3	ICS 1204	A	A	../uploads/proofs/proof_proof_before_6a416f3b9f08d4.98798423.jpg	../uploads/proofs/proof_proof_after_6a416f3ba271f8.37151497.png	2026-06-28 22:00:11.673705+03
\.


--
-- TOC entry 5132 (class 0 OID 16494)
-- Dependencies: 222
-- Data for Name: bookings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bookings (id, learner_id, tutor_id, unit_code, booking_date, status) FROM stdin;
1	3	4	ICS 1204	2026-07-06 19:00:00	approved
\.


--
-- TOC entry 5136 (class 0 OID 16541)
-- Dependencies: 226
-- Data for Name: feedback; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.feedback (id, booking_id, learner_id, tutor_id, comments, created_at) FROM stdin;
\.


--
-- TOC entry 5146 (class 0 OID 16766)
-- Dependencies: 236
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notifications (notification_id, user_id, booking_id, title, message, is_read, created_at) FROM stdin;
\.


--
-- TOC entry 5148 (class 0 OID 16791)
-- Dependencies: 238
-- Data for Name: payments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payments (payment_id, user_id, amount, transaction_ref, status, paid_at, created_at) FROM stdin;
\.


--
-- TOC entry 5138 (class 0 OID 16568)
-- Dependencies: 228
-- Data for Name: progress_reports; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.progress_reports (id, booking_id, tutor_id, learner_id, performance_assessment, academic_remarks, created_at, grade_achieved) FROM stdin;
\.


--
-- TOC entry 5134 (class 0 OID 16516)
-- Dependencies: 224
-- Data for Name: ratings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ratings (id, booking_id, learner_id, tutor_id, rating, created_at) FROM stdin;
\.


--
-- TOC entry 5144 (class 0 OID 16687)
-- Dependencies: 234
-- Data for Name: tutor_availability; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tutor_availability (availability_id, tutor_id, day_of_week, start_time, end_time) FROM stdin;
1	4	Monday	15:30:00	20:30:00
2	4	Tuesday	15:30:00	20:30:00
\.


--
-- TOC entry 5142 (class 0 OID 16669)
-- Dependencies: 232
-- Data for Name: tutor_credentials; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tutor_credentials (credential_id, tutor_id, unit_code, transcript_path, submission_status, submitted_at) FROM stdin;
1	4	ICS 1204	../uploads/credentials/tutor_4_a8cf808e71f6459062919d80ac5caf1f.pdf	approved	2026-06-23 10:07:36.853287
2	4	ICS 1204	../uploads/credentials/tutor_4_bfdd3273c8f9476a1c0905715e598e8b.pdf	approved	2026-06-23 10:52:17.36817
\.


--
-- TOC entry 5140 (class 0 OID 16619)
-- Dependencies: 230
-- Data for Name: tutor_units; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tutor_units (unit_id, tutor_id, unit_name) FROM stdin;
\.


--
-- TOC entry 5130 (class 0 OID 16388)
-- Dependencies: 220
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, password_hash, role, created_at, status) FROM stdin;
3	Delight Wambui Kanja	Delight.Kanja@strathmore.edu	$2y$10$OPxZI5hqetbE61naedahW.da5u1FGoIuWLyy/uIKBLa8olYHUHByy	learner	2026-06-18 09:19:00.776971	active
1	Duane Barrack Makedi	Duane.Makedi@strathmore.edu	$2y$10$yZ0qXvGrYAcz..ZlgWQB/ujAfrvdDq6wfVJ9BMEda7P7bVKEwaZ/q	admin	2026-06-17 23:08:38.694315	active
2	Natasha Mugoi Kemunto	Mogoi.Natasha@strathmore.edu	$2y$10$yZ0qXvGrYAcz..ZlgWQB/ujAfrvdDq6wfVJ9BMEda7P7bVKEwaZ/q	admin	2026-06-18 00:35:18.194254	active
4	Maina Austin Ndiangui	Ndiangui.Austin@strathmore.edu	$2y$10$J14cwC4BZosObitvDy59qesuaKpPV.iVb2zYLhcOlbUP75PizOIKe	tutor	2026-06-18 13:19:12.649034	active
\.


--
-- TOC entry 5167 (class 0 OID 0)
-- Dependencies: 239
-- Name: academic_progress_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.academic_progress_id_seq', 1, true);


--
-- TOC entry 5168 (class 0 OID 0)
-- Dependencies: 221
-- Name: bookings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bookings_id_seq', 1, true);


--
-- TOC entry 5169 (class 0 OID 0)
-- Dependencies: 225
-- Name: feedback_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.feedback_id_seq', 1, false);


--
-- TOC entry 5170 (class 0 OID 0)
-- Dependencies: 235
-- Name: notifications_notification_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notifications_notification_id_seq', 1, false);


--
-- TOC entry 5171 (class 0 OID 0)
-- Dependencies: 237
-- Name: payments_payment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payments_payment_id_seq', 1, false);


--
-- TOC entry 5172 (class 0 OID 0)
-- Dependencies: 227
-- Name: progress_reports_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.progress_reports_id_seq', 1, false);


--
-- TOC entry 5173 (class 0 OID 0)
-- Dependencies: 223
-- Name: ratings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.ratings_id_seq', 1, false);


--
-- TOC entry 5174 (class 0 OID 0)
-- Dependencies: 233
-- Name: tutor_availability_availability_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tutor_availability_availability_id_seq', 2, true);


--
-- TOC entry 5175 (class 0 OID 0)
-- Dependencies: 231
-- Name: tutor_credentials_credential_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tutor_credentials_credential_id_seq', 2, true);


--
-- TOC entry 5176 (class 0 OID 0)
-- Dependencies: 229
-- Name: tutor_units_unit_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tutor_units_unit_id_seq', 1, false);


--
-- TOC entry 5177 (class 0 OID 0)
-- Dependencies: 219
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 4, true);


--
-- TOC entry 4963 (class 2606 OID 16828)
-- Name: academic_progress academic_progress_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.academic_progress
    ADD CONSTRAINT academic_progress_pkey PRIMARY KEY (id);


--
-- TOC entry 4941 (class 2606 OID 16504)
-- Name: bookings bookings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_pkey PRIMARY KEY (id);


--
-- TOC entry 4945 (class 2606 OID 16551)
-- Name: feedback feedback_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.feedback
    ADD CONSTRAINT feedback_pkey PRIMARY KEY (id);


--
-- TOC entry 4957 (class 2606 OID 16779)
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (notification_id);


--
-- TOC entry 4959 (class 2606 OID 16803)
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (payment_id);


--
-- TOC entry 4961 (class 2606 OID 16805)
-- Name: payments payments_transaction_ref_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_transaction_ref_key UNIQUE (transaction_ref);


--
-- TOC entry 4947 (class 2606 OID 16579)
-- Name: progress_reports progress_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progress_reports
    ADD CONSTRAINT progress_reports_pkey PRIMARY KEY (id);


--
-- TOC entry 4943 (class 2606 OID 16524)
-- Name: ratings ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ratings
    ADD CONSTRAINT ratings_pkey PRIMARY KEY (id);


--
-- TOC entry 4953 (class 2606 OID 16697)
-- Name: tutor_availability tutor_availability_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_availability
    ADD CONSTRAINT tutor_availability_pkey PRIMARY KEY (availability_id);


--
-- TOC entry 4951 (class 2606 OID 16680)
-- Name: tutor_credentials tutor_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_credentials
    ADD CONSTRAINT tutor_credentials_pkey PRIMARY KEY (credential_id);


--
-- TOC entry 4949 (class 2606 OID 16625)
-- Name: tutor_units tutor_units_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_units
    ADD CONSTRAINT tutor_units_pkey PRIMARY KEY (unit_id);


--
-- TOC entry 4955 (class 2606 OID 16699)
-- Name: tutor_availability unique_tutor_day_time; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_availability
    ADD CONSTRAINT unique_tutor_day_time UNIQUE (tutor_id, day_of_week, start_time, end_time);


--
-- TOC entry 4937 (class 2606 OID 16402)
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- TOC entry 4939 (class 2606 OID 16400)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 4964 (class 2606 OID 16505)
-- Name: bookings bookings_learner_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_learner_id_fkey FOREIGN KEY (learner_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4965 (class 2606 OID 16510)
-- Name: bookings bookings_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookings
    ADD CONSTRAINT bookings_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4969 (class 2606 OID 16552)
-- Name: feedback feedback_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.feedback
    ADD CONSTRAINT feedback_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- TOC entry 4970 (class 2606 OID 16557)
-- Name: feedback feedback_learner_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.feedback
    ADD CONSTRAINT feedback_learner_id_fkey FOREIGN KEY (learner_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4971 (class 2606 OID 16562)
-- Name: feedback feedback_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.feedback
    ADD CONSTRAINT feedback_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4981 (class 2606 OID 16829)
-- Name: academic_progress fk_learner; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.academic_progress
    ADD CONSTRAINT fk_learner FOREIGN KEY (learner_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4978 (class 2606 OID 16785)
-- Name: notifications notifications_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE SET NULL;


--
-- TOC entry 4979 (class 2606 OID 16780)
-- Name: notifications notifications_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4980 (class 2606 OID 16806)
-- Name: payments payments_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4972 (class 2606 OID 16580)
-- Name: progress_reports progress_reports_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progress_reports
    ADD CONSTRAINT progress_reports_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- TOC entry 4973 (class 2606 OID 16590)
-- Name: progress_reports progress_reports_learner_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progress_reports
    ADD CONSTRAINT progress_reports_learner_id_fkey FOREIGN KEY (learner_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4974 (class 2606 OID 16585)
-- Name: progress_reports progress_reports_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progress_reports
    ADD CONSTRAINT progress_reports_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4966 (class 2606 OID 16525)
-- Name: ratings ratings_booking_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ratings
    ADD CONSTRAINT ratings_booking_id_fkey FOREIGN KEY (booking_id) REFERENCES public.bookings(id) ON DELETE CASCADE;


--
-- TOC entry 4967 (class 2606 OID 16530)
-- Name: ratings ratings_learner_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ratings
    ADD CONSTRAINT ratings_learner_id_fkey FOREIGN KEY (learner_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4968 (class 2606 OID 16535)
-- Name: ratings ratings_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ratings
    ADD CONSTRAINT ratings_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4977 (class 2606 OID 16700)
-- Name: tutor_availability tutor_availability_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_availability
    ADD CONSTRAINT tutor_availability_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4976 (class 2606 OID 16681)
-- Name: tutor_credentials tutor_credentials_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_credentials
    ADD CONSTRAINT tutor_credentials_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4975 (class 2606 OID 16626)
-- Name: tutor_units tutor_units_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutor_units
    ADD CONSTRAINT tutor_units_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.users(id) ON DELETE CASCADE;


-- Completed on 2026-06-29 10:26:51

--
-- PostgreSQL database dump complete
--

\unrestrict Y6XZT42vOxoOalS7pPsMjo8Ox1ZSSjttbBcoBCurAVBPjz0ICFwaR1A4hjY99wQ

