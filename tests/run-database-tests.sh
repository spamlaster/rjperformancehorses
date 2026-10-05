#!/bin/sh
set -eu
# Existing local Supabase database; fixtures always roll back.
docker exec -i supabase_db_rjperformancehorses psql -U postgres -d postgres -v ON_ERROR_STOP=1 < tests/availability.sql
