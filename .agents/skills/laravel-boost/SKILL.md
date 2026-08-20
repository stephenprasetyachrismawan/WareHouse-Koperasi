---
name: laravel-boost
description: Standards and guidelines for Laravel Boost and WareHouse-Koperasi architecture
---

# Laravel Boost Skill for WareHouse-Koperasi

When implementing Laravel code in this repository, follow these core conventions:

## 1. Guideline & Document Alignment
- Read project rules in `.ai/guidelines/warehouse-project.md`, `AGENTS.md`, `SECURITY-RULES.md`, and `ARCHITECTURE.md`.
- Never bypass `warehouse_id` tenant scoping on tenant models.
- Controllers must remain thin. Use Actions for mutations and Query Objects for complex reads.

## 2. Testing & Quality Tools
- Use Pest / PHPUnit for all tests (`tests/Feature/` and `tests/Unit/`).
- Use Laravel Pint for code formatting (`vendor/bin/pint`).
- Use PHPStan for static analysis (`vendor/bin/phpstan analyse`).

## 3. Database & Security
- Use backed Enums for status fields.
- Use explicit Form Requests for validation.
- All tenant-owned records must be authorized via Policies/Gates.
