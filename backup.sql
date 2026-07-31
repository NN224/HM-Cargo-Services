--
-- PostgreSQL database dump
--

\restrict dqPb0T9TaehaHFCpb6hCGuoU1Bfd93766UYApqs5jMScEO1wq0n974y2peOxZOo

-- Dumped from database version 18.4 (Debian 18.4-1.pgdg13+1)
-- Dumped by pg_dump version 18.4

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
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    action character varying(255) NOT NULL,
    auditable_type character varying(255) NOT NULL,
    auditable_id bigint NOT NULL,
    before json NOT NULL,
    after json NOT NULL,
    reason text NOT NULL,
    created_at timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.audit_logs OWNER TO postgres;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_logs_id_seq OWNER TO postgres;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.batches (
    id bigint NOT NULL,
    reference character varying(255) NOT NULL,
    route_id bigint NOT NULL,
    status character varying(255) NOT NULL,
    cost_per_kg_cents bigint,
    dispatched_on date,
    arrived_on date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.batches OWNER TO postgres;

--
-- Name: batches_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.batches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.batches_id_seq OWNER TO postgres;

--
-- Name: batches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.batches_id_seq OWNED BY public.batches.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: customer_rates; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.customer_rates (
    id bigint NOT NULL,
    customer_id bigint NOT NULL,
    route_id bigint NOT NULL,
    rate_per_kg_cents bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.customer_rates OWNER TO postgres;

--
-- Name: customer_rates_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.customer_rates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.customer_rates_id_seq OWNER TO postgres;

--
-- Name: customer_rates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.customer_rates_id_seq OWNED BY public.customer_rates.id;


--
-- Name: customers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.customers (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    phone character varying(255) NOT NULL,
    is_credit_customer boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.customers OWNER TO postgres;

--
-- Name: customers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.customers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.customers_id_seq OWNER TO postgres;

--
-- Name: customers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.customers_id_seq OWNED BY public.customers.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: package_status_events; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.package_status_events (
    id bigint NOT NULL,
    package_id bigint NOT NULL,
    status character varying(255) NOT NULL,
    warehouse_id bigint NOT NULL,
    user_id bigint NOT NULL,
    scanned_at timestamp(0) without time zone NOT NULL,
    source character varying(255) NOT NULL,
    note text,
    previous_status character varying(255),
    event_kind character varying(255) DEFAULT 'progress'::character varying NOT NULL,
    private_reason text,
    public_reason text
);


ALTER TABLE public.package_status_events OWNER TO postgres;

--
-- Name: package_status_events_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.package_status_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.package_status_events_id_seq OWNER TO postgres;

--
-- Name: package_status_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.package_status_events_id_seq OWNED BY public.package_status_events.id;


--
-- Name: packages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.packages (
    id bigint NOT NULL,
    shipment_id bigint NOT NULL,
    barcode character varying(255) NOT NULL,
    source_barcode character varying(255),
    description character varying(255),
    weight_kg numeric(12,4) NOT NULL,
    status character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    custom_rate_per_kg_cents integer,
    is_delayed boolean DEFAULT false NOT NULL,
    delay_reason text,
    delay_reason_is_public boolean DEFAULT false NOT NULL,
    delayed_at timestamp(0) without time zone
);


ALTER TABLE public.packages OWNER TO postgres;

--
-- Name: packages_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.packages_id_seq OWNER TO postgres;

--
-- Name: packages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.packages_id_seq OWNED BY public.packages.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: payment_allocations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payment_allocations (
    id bigint NOT NULL,
    payment_id bigint NOT NULL,
    shipment_id bigint NOT NULL,
    amount_cents bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payment_allocations OWNER TO postgres;

--
-- Name: payment_allocations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payment_allocations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payment_allocations_id_seq OWNER TO postgres;

--
-- Name: payment_allocations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payment_allocations_id_seq OWNED BY public.payment_allocations.id;


--
-- Name: payments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payments (
    id bigint NOT NULL,
    customer_id bigint NOT NULL,
    amount_cents bigint NOT NULL,
    method character varying(255) NOT NULL,
    custom_method_name character varying(255),
    collected_at timestamp(0) without time zone NOT NULL,
    collected_by bigint NOT NULL,
    warehouse_id bigint NOT NULL,
    reference character varying(255),
    notes text,
    receipt_number character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    reverses_payment_id bigint,
    reversal_reason text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.payments OWNER TO postgres;

--
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payments_id_seq OWNER TO postgres;

--
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- Name: routes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.routes (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    origin_warehouse_id bigint NOT NULL,
    destination_warehouse_id bigint NOT NULL,
    transit_warehouse_id bigint,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    origin_airport_name character varying(255),
    destination_airport_name character varying(255),
    delivery_office_name character varying(255)
);


ALTER TABLE public.routes OWNER TO postgres;

--
-- Name: routes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.routes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.routes_id_seq OWNER TO postgres;

--
-- Name: routes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.routes_id_seq OWNED BY public.routes.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: shipments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.shipments (
    id bigint NOT NULL,
    reference character varying(255) NOT NULL,
    public_token character varying(64) NOT NULL,
    customer_id bigint NOT NULL,
    recipient_name character varying(255) NOT NULL,
    recipient_phone character varying(255) NOT NULL,
    status character varying(255) NOT NULL,
    total_weight_kg numeric(12,4) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    batch_id bigint,
    rate_per_kg_cents bigint,
    computed_charge_cents bigint,
    final_charge_cents bigint,
    paid_amount_cents bigint DEFAULT '0'::bigint NOT NULL,
    priced_at timestamp(0) without time zone,
    destination_warehouse_id bigint,
    intake_notified_at timestamp(0) without time zone,
    arrival_notified_at timestamp(0) without time zone
);


ALTER TABLE public.shipments OWNER TO postgres;

--
-- Name: shipments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.shipments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.shipments_id_seq OWNER TO postgres;

--
-- Name: shipments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.shipments_id_seq OWNED BY public.shipments.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    role character varying(255) DEFAULT 'warehouse_employee'::character varying NOT NULL,
    warehouse_id bigint,
    is_active boolean DEFAULT true NOT NULL,
    capabilities json,
    locked_pages json
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: warehouses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.warehouses (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    location character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.warehouses OWNER TO postgres;

--
-- Name: warehouses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.warehouses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.warehouses_id_seq OWNER TO postgres;

--
-- Name: warehouses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.warehouses_id_seq OWNED BY public.warehouses.id;


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: batches id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.batches ALTER COLUMN id SET DEFAULT nextval('public.batches_id_seq'::regclass);


--
-- Name: customer_rates id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_rates ALTER COLUMN id SET DEFAULT nextval('public.customer_rates_id_seq'::regclass);


--
-- Name: customers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customers ALTER COLUMN id SET DEFAULT nextval('public.customers_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: package_status_events id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_status_events ALTER COLUMN id SET DEFAULT nextval('public.package_status_events_id_seq'::regclass);


--
-- Name: packages id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.packages ALTER COLUMN id SET DEFAULT nextval('public.packages_id_seq'::regclass);


--
-- Name: payment_allocations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_allocations ALTER COLUMN id SET DEFAULT nextval('public.payment_allocations_id_seq'::regclass);


--
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- Name: routes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes ALTER COLUMN id SET DEFAULT nextval('public.routes_id_seq'::regclass);


--
-- Name: shipments id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipments ALTER COLUMN id SET DEFAULT nextval('public.shipments_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: warehouses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.warehouses ALTER COLUMN id SET DEFAULT nextval('public.warehouses_id_seq'::regclass);


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.audit_logs (id, user_id, action, auditable_type, auditable_id, before, after, reason, created_at) FROM stdin;
1	1	package_journey_corrected	App\\Models\\Package	8	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
2	1	package_journey_corrected	App\\Models\\Package	9	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
3	1	package_journey_corrected	App\\Models\\Package	10	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
4	1	package_journey_corrected	App\\Models\\Package	11	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
5	1	package_journey_corrected	App\\Models\\Package	12	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
6	1	package_journey_corrected	App\\Models\\Package	13	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
7	1	package_journey_corrected	App\\Models\\Package	14	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
8	1	package_journey_corrected	App\\Models\\Package	15	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
9	1	package_journey_corrected	App\\Models\\Package	16	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
10	1	package_journey_corrected	App\\Models\\Package	17	{"status":"received_origin"}	{"status":"arrived_origin_airport"}	تصحيح جماعي من شاشة الشحنات	2026-07-29 13:21:51
\.


--
-- Data for Name: batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.batches (id, reference, route_id, status, cost_per_kg_cents, dispatched_on, arrived_on, created_at, updated_at) FROM stdin;
4	BEIRUT 29/7	2	dispatched	0	2026-07-29	\N	2026-07-29 12:47:22	2026-07-29 13:20:40
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
hm-cargo-services-cache-f5b21f6565abe8f351cff2d70ed47ee601475b88	i:1;	1785317058
hm-cargo-services-cache-56e69cc6b0494f3bf4c03a93615991edee33890b:timer	i:1785318045;	1785318045
hm-cargo-services-cache-56e69cc6b0494f3bf4c03a93615991edee33890b	i:1;	1785318045
hm-cargo-services-cache-livewire-rate-limiter:24f681e60ad931579c418576267b1e95a4eb310e:timer	i:1785053931;	1785053931
hm-cargo-services-cache-livewire-rate-limiter:24f681e60ad931579c418576267b1e95a4eb310e	i:1;	1785053931
hm-cargo-services-cache-1e9e18455c6effd8306c2db28117f3840fd87830:timer	i:1785077204;	1785077204
hm-cargo-services-cache-1e9e18455c6effd8306c2db28117f3840fd87830	i:1;	1785077204
hm-cargo-services-cache-livewire-rate-limiter:ccb50b4a2d429218d051ed06fdd412aa1cf0133c:timer	i:1785077587;	1785077587
hm-cargo-services-cache-livewire-rate-limiter:ccb50b4a2d429218d051ed06fdd412aa1cf0133c	i:1;	1785077587
hm-cargo-services-cache-livewire-rate-limiter:ee533260bb5048e50a954f3175f38cee74df8783:timer	i:1785144795;	1785144795
hm-cargo-services-cache-livewire-rate-limiter:ee533260bb5048e50a954f3175f38cee74df8783	i:1;	1785144795
hm-cargo-services-cache-livewire-rate-limiter:01fdb94ceb60eeae2e76062788f1e9727a6d0edd:timer	i:1785222425;	1785222425
hm-cargo-services-cache-livewire-rate-limiter:01fdb94ceb60eeae2e76062788f1e9727a6d0edd	i:1;	1785222425
hm-cargo-services-cache-livewire-rate-limiter:49ed0e1f581db433b1af66a571e76f03e005f941:timer	i:1785240185;	1785240185
hm-cargo-services-cache-livewire-rate-limiter:49ed0e1f581db433b1af66a571e76f03e005f941	i:1;	1785240185
hm-cargo-services-cache-livewire-rate-limiter:f68dbfd9cdd99f879d358ada73239873d1e9bd04:timer	i:1785314853;	1785314853
hm-cargo-services-cache-livewire-rate-limiter:f68dbfd9cdd99f879d358ada73239873d1e9bd04	i:1;	1785314853
hm-cargo-services-cache-0d1f9b433d4641faf8160a913ba9d96775d6c279:timer	i:1785316631;	1785316631
hm-cargo-services-cache-0d1f9b433d4641faf8160a913ba9d96775d6c279	i:1;	1785316631
hm-cargo-services-cache-d0fc25e61f908c6ff8128fb3a105cf40d8c86a62:timer	i:1785316710;	1785316710
hm-cargo-services-cache-d0fc25e61f908c6ff8128fb3a105cf40d8c86a62	i:1;	1785316710
hm-cargo-services-cache-2f0ed856eab9e1766409a33dea74bcdfd9c2e7bd:timer	i:1785316755;	1785316755
hm-cargo-services-cache-2f0ed856eab9e1766409a33dea74bcdfd9c2e7bd	i:1;	1785316755
hm-cargo-services-cache-8ae214271322bab13f87b393a5e3159d808f6fd6:timer	i:1785316779;	1785316779
hm-cargo-services-cache-livewire-rate-limiter:ca6bd1c531874dd2945c830b2dab0169552901b8:timer	i:1785318238;	1785318238
hm-cargo-services-cache-8ae214271322bab13f87b393a5e3159d808f6fd6	i:2;	1785316779
hm-cargo-services-cache-d78741c8f143af8fcaa8e65323b29b74912b4ce3:timer	i:1785316905;	1785316905
hm-cargo-services-cache-d78741c8f143af8fcaa8e65323b29b74912b4ce3	i:1;	1785316905
hm-cargo-services-cache-93fe831144d2424e640b8e1d803b53e6ea7734c9:timer	i:1785316905;	1785316905
hm-cargo-services-cache-93fe831144d2424e640b8e1d803b53e6ea7734c9	i:2;	1785316905
hm-cargo-services-cache-27699d797a65995c013810d1df6b1e9048fb8d4d:timer	i:1785316977;	1785316977
hm-cargo-services-cache-27699d797a65995c013810d1df6b1e9048fb8d4d	i:1;	1785316977
hm-cargo-services-cache-f5b21f6565abe8f351cff2d70ed47ee601475b88:timer	i:1785317058;	1785317058
hm-cargo-services-cache-livewire-rate-limiter:ca6bd1c531874dd2945c830b2dab0169552901b8	i:1;	1785318238
hm-cargo-services-cache-0846c34bab5b9e8a677f09b3d60fd9f1a5505429:timer	i:1785318597;	1785318597
hm-cargo-services-cache-0846c34bab5b9e8a677f09b3d60fd9f1a5505429	i:1;	1785318597
hm-cargo-services-cache-45117d661dba202165d69ddba2ff270e24aeae11:timer	i:1785318644;	1785318644
hm-cargo-services-cache-45117d661dba202165d69ddba2ff270e24aeae11	i:1;	1785318644
hm-cargo-services-cache-2759c15350443b8f36b0ecb877dbd3e4ccaed4ee:timer	i:1785318719;	1785318719
hm-cargo-services-cache-2759c15350443b8f36b0ecb877dbd3e4ccaed4ee	i:1;	1785318719
hm-cargo-services-cache-88b231ebeb5886f68401eccb789c46d3d90c85a6:timer	i:1785318722;	1785318722
hm-cargo-services-cache-88b231ebeb5886f68401eccb789c46d3d90c85a6	i:1;	1785318722
hm-cargo-services-cache-livewire-rate-limiter:9292747fb1d2a7c168d411c93575dad8ac06e37b:timer	i:1785358932;	1785358932
hm-cargo-services-cache-livewire-rate-limiter:9292747fb1d2a7c168d411c93575dad8ac06e37b	i:1;	1785358932
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: customer_rates; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.customer_rates (id, customer_id, route_id, rate_per_kg_cents, created_at, updated_at) FROM stdin;
2	3	2	925	2026-07-24 15:30:10	2026-07-24 15:30:10
3	22	2	1	2026-07-25 11:23:39	2026-07-25 11:23:39
\.


--
-- Data for Name: customers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.customers (id, name, phone, is_credit_customer, is_active, created_at, updated_at) FROM stdin;
3	NABIL CHAAR	+971543665548	f	t	2026-07-24 15:28:01	2026-07-24 15:28:01
4	ahmad m	+961 76 821 824	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
5	AMANI EL ASHI	+961 70 660 048	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
6	BILAL KATRANJE	+961 71 445 221	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
7	CARINE AWAD	+961 76 386 975	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
8	DIANA HASSAN	+961 70 699 075	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
9	ELINA CHAKOUR	+961 81 707 943	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
10	FATIMA FARASHA	+961 71 598 429	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
11	GHYDAA EL NADAF	+961 81 318 424	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
12	HASAN ABOLHASAN	+961 76 988 874	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
13	JANA MATTAR	+961 70 350 901	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
14	JESSY ISSA	+961 71 537 456	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
15	LAILA BAGHDADI	+963 940 881 483	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
16	LINA SALMAN	+961 76 823 868	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
17	MAGUY HAFEZ	+961 71 056 265	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
18	MARIANNE BAAKLINI	+961 71 064 967	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
19	MARIANNE SALIBA	+961 81 568 611	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
20	MAROUN MERHJ	+961 3 462 265	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
21	MARYAM MEHYDINE	+961 78 837 061	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
22	MONZER AWADA	+961 70 489 088	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
23	nabil	+971 54 366 5548	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
24	NABILLA AL ARAB	+961 3 595 116	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
25	NOUR AL HAJJ	+961 81 808 137	f	t	2026-07-25 08:51:04	2026-07-25 08:51:04
27	RENEE EL CHEIKH	+961 71 067 084	f	t	2026-07-25 09:26:25	2026-07-25 09:26:25
28	RIM ABOU HAMDAN	+961 70 275 050	f	t	2026-07-25 09:26:25	2026-07-25 09:26:25
29	SAMIRA EL NAHHAS	+961 70 041 071	f	t	2026-07-25 09:26:25	2026-07-25 09:26:25
30	SHIPSHARKS	+961 81 175 526	f	t	2026-07-25 09:26:25	2026-07-25 09:26:25
31	VANESSA MELHEM	+961 76 564 848	f	t	2026-07-25 09:26:25	2026-07-25 09:26:25
32	JAMES ABO JAWDEH 	+961 70 002 676	f	t	2026-07-29 12:48:56	2026-07-29 12:48:56
33	MAGY REKAB	+961 71 472 051	f	t	2026-07-29 12:54:47	2026-07-29 12:54:47
34	ASMARINA KARIM	+961 81 441 444	f	t	2026-07-29 12:56:30	2026-07-29 12:56:30
35	MAYA KHALED	+961 81 129 214	f	t	2026-07-29 13:01:00	2026-07-29 13:01:00
38	HIND DAKDOUK	+961 81 353 796	f	t	2026-07-29 13:11:49	2026-07-29 13:11:49
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_07_20_213150_create_warehouses_table	1
5	2026_07_20_213151_add_role_and_warehouse_to_users_table	1
6	2026_07_20_214046_create_customers_table	1
7	2026_07_20_214047_create_routes_table	1
8	2026_07_20_214048_create_customer_rates_table	1
9	2026_07_20_233132_create_shipments_table	1
10	2026_07_20_233133_create_packages_table	1
11	2026_07_20_234132_create_batches_table	1
12	2026_07_20_234133_add_pricing_to_shipments_table	1
13	2026_07_20_235503_add_capabilities_to_users_table	1
14	2026_07_21_000001_create_package_status_events_table	1
15	2026_07_21_044200_create_payments_table	1
16	2026_07_21_044201_create_payment_allocations_table	1
17	2026_07_21_124500_add_destination_to_shipments_table	1
18	2026_07_23_000001_add_locked_pages_to_users_table	1
19	2026_07_24_000001_add_notification_timestamps_to_shipments_table	2
20	2026_07_25_000001_add_custom_rate_to_packages_table	3
21	2026_07_25_220717_add_journey_labels_to_routes_table	4
22	2026_07_25_220718_add_journey_metadata_to_packages_and_events	4
23	2026_07_25_220719_create_audit_logs_table	4
24	2026_07_26_090657_update_orphan_package_statuses	4
\.


--
-- Data for Name: package_status_events; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.package_status_events (id, package_id, status, warehouse_id, user_id, scanned_at, source, note, previous_status, event_kind, private_reason, public_reason) FROM stdin;
4	8	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
5	14	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
6	11	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
7	15	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
8	12	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
9	13	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
10	9	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
11	16	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
12	10	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
13	17	received_origin	1	1	2026-07-29 13:19:24	batch_journey_progress	\N	created	progress	\N	\N
14	8	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
15	9	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
16	10	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
17	11	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
18	12	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
19	13	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
20	14	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
21	15	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
22	16	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
23	17	arrived_origin_airport	1	1	2026-07-29 13:21:51	journey_correction	\N	received_origin	correction	تصحيح جماعي من شاشة الشحنات	\N
24	17	in_transit	1	1	2026-07-29 13:23:14	selected_shipments_bulk_progress	\N	arrived_origin_airport	progress	\N	\N
25	17	arrived_transit	2	1	2026-07-29 13:48:37	selected_shipments_bulk_progress	\N	in_transit	progress	\N	\N
26	17	departed_transit	2	1	2026-07-29 13:50:05	selected_shipments_bulk_progress	\N	arrived_transit	progress	\N	\N
27	17	arrived_destination	2	1	2026-07-29 13:50:10	selected_shipments_bulk_progress	\N	departed_transit	progress	\N	\N
28	17	arrived_destination	2	1	2026-07-29 13:50:49	journey_note	\N	arrived_destination	note	\N	لقد وصلت شحنتك الى مستودع بيروت
\.


--
-- Data for Name: packages; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.packages (id, shipment_id, barcode, source_barcode, description, weight_kg, status, created_at, updated_at, custom_rate_per_kg_cents, is_delayed, delay_reason, delay_reason_is_public, delayed_at) FROM stdin;
8	6	PKG-0AS0RQZOQT	\N	MOBILE ACCESSORIES	12.0000	arrived_origin_airport	2026-07-29 12:50:27	2026-07-29 13:21:51	1100	f	\N	f	\N
9	7	PKG-PIMLZBHUMP	\N	SHEIN	1.0000	arrived_origin_airport	2026-07-29 12:51:08	2026-07-29 13:21:51	925	f	\N	f	\N
10	8	PKG-TMFBHOZHKU	\N	GENERAL GOODS	120.3000	arrived_origin_airport	2026-07-29 12:52:39	2026-07-29 13:21:51	1050	f	\N	f	\N
11	9	PKG-KOR9KT3FGM	1314	SHEIN	2.4000	arrived_origin_airport	2026-07-29 12:54:11	2026-07-29 13:21:51	925	f	\N	f	\N
12	10	PKG-ZWSDEKEWLM	5252	SHEIN	2.7000	arrived_origin_airport	2026-07-29 12:55:16	2026-07-29 13:21:51	925	f	\N	f	\N
13	11	PKG-7WZWUXSLKS	1348	SHEIN	3.0000	arrived_origin_airport	2026-07-29 12:57:26	2026-07-29 13:21:51	925	f	\N	f	\N
14	12	PKG-JFHZ1UDY1R	1371	SHEIN	2.6000	arrived_origin_airport	2026-07-29 13:09:37	2026-07-29 13:21:51	925	f	\N	f	\N
15	13	PKG-BOU2AOK9FP	\N	SHEIN	1.8000	arrived_origin_airport	2026-07-29 13:10:44	2026-07-29 13:21:51	925	f	\N	f	\N
16	14	PKG-KO2JNP1U9O	5965	SHEIN	4.6000	arrived_origin_airport	2026-07-29 13:12:15	2026-07-29 13:21:51	925	f	\N	f	\N
17	15	PKG-C3I8EAB7T2	12345	GENERAL GOODS	99.0000	arrived_destination	2026-07-29 13:17:03	2026-07-29 13:50:10	1050	f	\N	f	\N
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: payment_allocations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payment_allocations (id, payment_id, shipment_id, amount_cents, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: payments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payments (id, customer_id, amount_cents, method, custom_method_name, collected_at, collected_by, warehouse_id, reference, notes, receipt_number, type, reverses_payment_id, reversal_reason, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: routes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.routes (id, name, origin_warehouse_id, destination_warehouse_id, transit_warehouse_id, is_active, created_at, updated_at, origin_airport_name, destination_airport_name, delivery_office_name) FROM stdin;
2	Dubai → Lebanon	1	2	\N	t	2026-07-24 09:49:43	2026-07-26 11:42:19	مطار دبي	مطار بيروت	مكتب بيروت
3	Dubai → Syria (Direct)	1	3	\N	t	2026-07-24 09:49:43	2026-07-26 11:42:19	مطار دبي	مطار دمشق	مكتب دمشق
4	Dubai → Beirut → Syria	1	3	2	t	2026-07-24 09:49:43	2026-07-26 11:42:19	مطار دبي	مطار بيروت	مكتب دمشق
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
UwtCrAHSyBB4gWeb8A0VrmsurfKyPMSv7bih2eRu	1	162.159.122.174	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJscTFnQk1xY0tHVHQ2cEZXWWlwWjBnQVc0N2RzVXRSa1VFajBaT0luIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsInBhc3N3b3JkX2hhc2hfd2ViIjoiMjFiYzI4YzViMDhhNDY4MzIwMmVmNjMyZTdkMzllMjI3ODUxZjliYWM4ZDlmNjZhMTUzNDBjY2MyYmY3OTBiNSIsIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwczpcL1wvc3lzdGVtLmhtY2FyZ29zZXJ2aWNlcy5jb21cL2htMjAyNCIsInJvdXRlIjoiZmlsYW1lbnQuYWRtaW4ucGFnZXMuZGFzaGJvYXJkIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwidGFibGVzIjp7Ijk2YjZlM2RlNDA3YmU0NWZkYTgyZjA4ZTFlOTZiZDY5X2NvbHVtbnMiOlt7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoicmVmZXJlbmNlIiwibGFiZWwiOiJcdTA2MzFcdTA2NDJcdTA2NDUgXHUwNjI3XHUwNjQ0XHUwNjM0XHUwNjJkXHUwNjQ2XHUwNjI5IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6ImN1c3RvbWVyLm5hbWUiLCJsYWJlbCI6Ilx1MDYyN1x1MDY0NFx1MDYzOVx1MDY0NVx1MDY0YVx1MDY0NCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJyZWNpcGllbnRfbmFtZSIsImxhYmVsIjoiXHUwNjI3XHUwNjQ0XHUwNjQ1XHUwNjMzXHUwNjJhXHUwNjQ0XHUwNjQ1IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6ImJhdGNoLnJvdXRlLm5hbWUiLCJsYWJlbCI6Ilx1MDYyZVx1MDYzNyBcdTA2MjdcdTA2NDRcdTA2MzRcdTA2MmRcdTA2NDYiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic3RhdHVzIiwibGFiZWwiOiJcdTA2MjdcdTA2NDRcdTA2MmRcdTA2MjdcdTA2NDRcdTA2MjkiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY3JlYXRlZF9hdCIsImxhYmVsIjoiXHUwNjJhXHUwNjI3XHUwNjMxXHUwNjRhXHUwNjJlIFx1MDYyN1x1MDY0NFx1MDYyN1x1MDYzM1x1MDYyYVx1MDY0NFx1MDYyN1x1MDY0NSIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9XSwiYjMxMWVhYzQ0ZGViNWNiMTg1YTgxMzI1Y2YzZDRlNGVfY29sdW1ucyI6W3sidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJuYW1lIiwibGFiZWwiOiJcdTA2MjdcdTA2NDRcdTA2MzlcdTA2NDVcdTA2NGFcdTA2NDQiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoicGhvbmUiLCJsYWJlbCI6Ilx1MDYzMVx1MDY0Mlx1MDY0NSBcdTA2MjdcdTA2NDRcdTA2NDdcdTA2MjdcdTA2MmFcdTA2NDEiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoib3V0c3RhbmRpbmdfY2VudHMiLCJsYWJlbCI6Ilx1MDYyN1x1MDY0NFx1MDY0NVx1MDYyYVx1MDYyOFx1MDY0Mlx1MDY0YSIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9XX19	1785383460
\.


--
-- Data for Name: shipments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.shipments (id, reference, public_token, customer_id, recipient_name, recipient_phone, status, total_weight_kg, created_at, updated_at, batch_id, rate_per_kg_cents, computed_charge_cents, final_charge_cents, paid_amount_cents, priced_at, destination_warehouse_id, intake_notified_at, arrival_notified_at) FROM stdin;
15	HM-2026-000015	be4fb3c7d2c84330716eaeb501e3a9d1fd370cefc32722c2	4	ahmad m	+961 76 821 824	ready_for_collection	99.0000	2026-07-29 13:17:03	2026-07-29 13:52:37	4	\N	0	105000	0	2026-07-29 13:17:03	2	2026-07-29 13:17:25	2026-07-29 13:52:37
6	HM-2026-000001	4f134bf3c80f17c5069b9e513f9a2e1cb75f4e04d6a519cb	32	JAMES ABO JAWDEH 	+961 70 002 676	in_transit	12.0000	2026-07-29 12:50:27	2026-07-29 13:21:51	4	\N	0	13200	0	2026-07-29 12:50:27	2	\N	\N
7	HM-2026-000007	40064c40557928e4d49c0c99650a7eff7a8f505b91d0326f	29	SAMIRA EL NAHHAS	+961 70 041 071	in_transit	1.0000	2026-07-29 12:51:08	2026-07-29 13:21:51	4	\N	0	900	0	2026-07-29 12:51:08	2	\N	\N
8	HM-2026-000008	c2f0a92cde87e73ede97be874e3ee10e312e340da1cfcf1a	30	SHIPSHARKS	+961 81 175 526	in_transit	120.3000	2026-07-29 12:52:39	2026-07-29 13:21:51	4	\N	0	126300	0	2026-07-29 12:52:39	2	2026-07-29 13:16:04	\N
9	HM-2026-000009	0f45fee1e421c471d24d087a7c228929eb8dbf705a7b686e	12	HASAN ABOLHASAN	+961 76 988 874	in_transit	2.4000	2026-07-29 12:54:11	2026-07-29 13:21:51	4	\N	0	2200	0	2026-07-29 12:54:11	2	\N	\N
10	HM-2026-000010	b715023e37407057f22b59a881365d082b86dff0d4671b38	33	MAGY REKAB	+961 71 472 051	in_transit	2.7000	2026-07-29 12:55:16	2026-07-29 13:21:51	4	\N	0	2500	0	2026-07-29 12:55:16	2	\N	\N
11	HM-2026-000011	58ee333e92a9a6ce436afa63cfe0aba25268c06e37ef12fa	34	ASMARINA KARIM	+961 81 441 444	in_transit	3.0000	2026-07-29 12:57:26	2026-07-29 13:21:51	4	\N	0	2800	0	2026-07-29 12:57:26	2	\N	\N
12	HM-2026-000012	504d9e6246e2b47701a9076643608c2d34646ea15fb7bc3e	35	MAYA KHALED	+961 81 129 214	in_transit	2.6000	2026-07-29 13:09:37	2026-07-29 13:21:51	4	\N	0	2400	0	2026-07-29 13:09:37	2	\N	\N
13	HM-2026-000013	b916b221f24a06458d7315594b0871000b8f8a66a7c0b93d	31	VANESSA MELHEM	+961 76 564 848	in_transit	1.8000	2026-07-29 13:10:44	2026-07-29 13:21:51	4	\N	0	1700	0	2026-07-29 13:10:44	2	\N	\N
14	HM-2026-000014	4983e43cf49d320c22882010d220bcbd51bdee7f818af623	38	HIND DAKDOUK	+961 81 353 796	in_transit	4.6000	2026-07-29 13:12:15	2026-07-29 13:21:51	4	\N	0	4300	0	2026-07-29 13:12:15	2	\N	\N
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, role, warehouse_id, is_active, capabilities, locked_pages) FROM stdin;
1	مدير النظام	admin@hmcargo.ae	\N	$2y$12$AA48iPjE.xkYgavCkVkBTuDPzT72UOJOoSGGzZ1Wg6YmHvVhmvThC	0solgIvnIzXFCsSOdvvzye5JmgNbF9Pcy7qCbaU2kQxpaz7t2FiWTeVZbiUp	2026-07-23 14:17:59	2026-07-23 14:17:59	administrator	1	t	\N	\N
\.


--
-- Data for Name: warehouses; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.warehouses (id, name, location, is_active, created_at, updated_at) FROM stdin;
1	Dubai	دبي، الإمارات العربية المتحدة	t	2026-07-23 14:17:59	2026-07-23 14:17:59
2	Beirut	بيروت، لبنان	t	2026-07-23 14:17:59	2026-07-23 14:17:59
3	Damascus	دمشق، سوريا	t	2026-07-23 14:17:59	2026-07-23 14:17:59
\.


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 10, true);


--
-- Name: batches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.batches_id_seq', 4, true);


--
-- Name: customer_rates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.customer_rates_id_seq', 3, true);


--
-- Name: customers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.customers_id_seq', 38, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 24, true);


--
-- Name: package_status_events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.package_status_events_id_seq', 28, true);


--
-- Name: packages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.packages_id_seq', 17, true);


--
-- Name: payment_allocations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payment_allocations_id_seq', 3, true);


--
-- Name: payments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payments_id_seq', 3, true);


--
-- Name: routes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.routes_id_seq', 4, true);


--
-- Name: shipments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.shipments_id_seq', 15, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- Name: warehouses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.warehouses_id_seq', 3, true);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: batches batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.batches
    ADD CONSTRAINT batches_pkey PRIMARY KEY (id);


--
-- Name: batches batches_reference_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.batches
    ADD CONSTRAINT batches_reference_unique UNIQUE (reference);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: customer_rates customer_rates_customer_id_route_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_rates
    ADD CONSTRAINT customer_rates_customer_id_route_id_unique UNIQUE (customer_id, route_id);


--
-- Name: customer_rates customer_rates_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_rates
    ADD CONSTRAINT customer_rates_pkey PRIMARY KEY (id);


--
-- Name: customers customers_phone_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customers
    ADD CONSTRAINT customers_phone_unique UNIQUE (phone);


--
-- Name: customers customers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customers
    ADD CONSTRAINT customers_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: package_status_events package_status_events_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_status_events
    ADD CONSTRAINT package_status_events_pkey PRIMARY KEY (id);


--
-- Name: packages packages_barcode_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.packages
    ADD CONSTRAINT packages_barcode_unique UNIQUE (barcode);


--
-- Name: packages packages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.packages
    ADD CONSTRAINT packages_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payment_allocations payment_allocations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_allocations
    ADD CONSTRAINT payment_allocations_pkey PRIMARY KEY (id);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: payments payments_receipt_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_receipt_number_unique UNIQUE (receipt_number);


--
-- Name: routes routes_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_name_unique UNIQUE (name);


--
-- Name: routes routes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: shipments shipments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipments
    ADD CONSTRAINT shipments_pkey PRIMARY KEY (id);


--
-- Name: shipments shipments_public_token_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipments
    ADD CONSTRAINT shipments_public_token_unique UNIQUE (public_token);


--
-- Name: shipments shipments_reference_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipments
    ADD CONSTRAINT shipments_reference_unique UNIQUE (reference);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: warehouses warehouses_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.warehouses
    ADD CONSTRAINT warehouses_name_unique UNIQUE (name);


--
-- Name: warehouses warehouses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.warehouses
    ADD CONSTRAINT warehouses_pkey PRIMARY KEY (id);


--
-- Name: audit_logs_auditable_type_auditable_id_created_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX audit_logs_auditable_type_auditable_id_created_at_index ON public.audit_logs USING btree (auditable_type, auditable_id, created_at);


--
-- Name: batches_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX batches_status_index ON public.batches USING btree (status);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: customers_name_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX customers_name_index ON public.customers USING btree (name);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: package_status_events_package_id_scanned_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX package_status_events_package_id_scanned_at_index ON public.package_status_events USING btree (package_id, scanned_at);


--
-- Name: package_status_events_warehouse_id_scanned_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX package_status_events_warehouse_id_scanned_at_index ON public.package_status_events USING btree (warehouse_id, scanned_at);


--
-- Name: packages_shipment_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX packages_shipment_id_status_index ON public.packages USING btree (shipment_id, status);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: shipments_batch_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX shipments_batch_id_index ON public.shipments USING btree (batch_id);


--
-- Name: shipments_customer_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX shipments_customer_id_index ON public.shipments USING btree (customer_id);


--
-- Name: shipments_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX shipments_status_index ON public.shipments USING btree (status);


--
-- Name: audit_logs audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: batches batches_route_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.batches
    ADD CONSTRAINT batches_route_id_foreign FOREIGN KEY (route_id) REFERENCES public.routes(id) ON DELETE RESTRICT;


--
-- Name: customer_rates customer_rates_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_rates
    ADD CONSTRAINT customer_rates_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE RESTRICT;


--
-- Name: customer_rates customer_rates_route_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.customer_rates
    ADD CONSTRAINT customer_rates_route_id_foreign FOREIGN KEY (route_id) REFERENCES public.routes(id) ON DELETE RESTRICT;


--
-- Name: package_status_events package_status_events_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_status_events
    ADD CONSTRAINT package_status_events_package_id_foreign FOREIGN KEY (package_id) REFERENCES public.packages(id) ON DELETE RESTRICT;


--
-- Name: package_status_events package_status_events_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_status_events
    ADD CONSTRAINT package_status_events_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: package_status_events package_status_events_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.package_status_events
    ADD CONSTRAINT package_status_events_warehouse_id_foreign FOREIGN KEY (warehouse_id) REFERENCES public.warehouses(id) ON DELETE RESTRICT;


--
-- Name: packages packages_shipment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.packages
    ADD CONSTRAINT packages_shipment_id_foreign FOREIGN KEY (shipment_id) REFERENCES public.shipments(id) ON DELETE CASCADE;


--
-- Name: payment_allocations payment_allocations_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_allocations
    ADD CONSTRAINT payment_allocations_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE RESTRICT;


--
-- Name: payment_allocations payment_allocations_shipment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_allocations
    ADD CONSTRAINT payment_allocations_shipment_id_foreign FOREIGN KEY (shipment_id) REFERENCES public.shipments(id) ON DELETE RESTRICT;


--
-- Name: payments payments_collected_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_collected_by_foreign FOREIGN KEY (collected_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: payments payments_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE RESTRICT;


--
-- Name: payments payments_reverses_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_reverses_payment_id_foreign FOREIGN KEY (reverses_payment_id) REFERENCES public.payments(id) ON DELETE RESTRICT;


--
-- Name: payments payments_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_warehouse_id_foreign FOREIGN KEY (warehouse_id) REFERENCES public.warehouses(id) ON DELETE RESTRICT;


--
-- Name: routes routes_destination_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_destination_warehouse_id_foreign FOREIGN KEY (destination_warehouse_id) REFERENCES public.warehouses(id) ON DELETE RESTRICT;


--
-- Name: routes routes_origin_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_origin_warehouse_id_foreign FOREIGN KEY (origin_warehouse_id) REFERENCES public.warehouses(id) ON DELETE RESTRICT;


--
-- Name: routes routes_transit_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_transit_warehouse_id_foreign FOREIGN KEY (transit_warehouse_id) REFERENCES public.warehouses(id) ON DELETE RESTRICT;


--
-- Name: shipments shipments_batch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipments
    ADD CONSTRAINT shipments_batch_id_foreign FOREIGN KEY (batch_id) REFERENCES public.batches(id) ON DELETE SET NULL;


--
-- Name: shipments shipments_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipments
    ADD CONSTRAINT shipments_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.customers(id) ON DELETE RESTRICT;


--
-- Name: shipments shipments_destination_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipments
    ADD CONSTRAINT shipments_destination_warehouse_id_foreign FOREIGN KEY (destination_warehouse_id) REFERENCES public.warehouses(id) ON DELETE RESTRICT;


--
-- Name: users users_warehouse_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_warehouse_id_foreign FOREIGN KEY (warehouse_id) REFERENCES public.warehouses(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict dqPb0T9TaehaHFCpb6hCGuoU1Bfd93766UYApqs5jMScEO1wq0n974y2peOxZOo

