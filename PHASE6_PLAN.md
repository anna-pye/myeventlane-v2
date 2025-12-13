# Phase 6: Boost, Category Follow, Digests, and Notifications - Implementation Plan

## Current State Assessment

### ✅ Already Implemented

1. **Boost Feature**
   - ✅ BoostManager service exists
   - ✅ Boost products can be created (scripts available)
   - ✅ Boost applies to events via field_promoted and field_promo_expires
   - ✅ BoostOrderSubscriber applies boost on order completion
   - ✅ Boost expiry cron job exists
   - ⚠️ Need to verify: Boost products configured with correct pricing (7 days @ $5/day, 10 days @ $4/day, 30 days @ $3/day)
   - ⚠️ Need to verify: Views sort boosted events first
   - ⚠️ Need to verify: "Featured" badge shown on event cards

2. **Email Digests**
   - ✅ VendorDigestGenerator exists (for vendors)
   - ✅ Email templates exist
   - ❌ Need to implement: User category digest (for users who follow categories)

### ❌ Missing/Needs Enhancement

1. **Boost Products**
   - Need to create/verify boost products with correct pricing
   - Need to ensure Views sort boosted events first
   - Need to ensure "Featured" badge appears on event cards

2. **Category Following**
   - Flag module not installed (need to install)
   - Need to create flag for following categories
   - Need to implement /my-categories page
   - Need to show "new this week" events per category

3. **User Category Digests**
   - Need to create weekly digest for users who follow categories
   - Need to include boosted events prominently
   - Need to include .ics links

## Implementation Tasks

### Task 1: Verify and Configure Boost Products
- Create boost products with correct pricing:
  - 7 days at $5/day = $35
  - 10 days at $4/day = $40
  - 30 days at $3/day = $90
- Ensure boost products are linked to stores

### Task 2: Ensure Boosted Events Sort First
- Update Views to sort by field_promoted first, then by date
- Verify "Featured" badge appears on event cards

### Task 3: Install and Configure Flag Module
- Install Flag module
- Create "follow_category" flag
- Configure flag for taxonomy terms (categories)

### Task 4: Create My Categories Page
- Create /my-categories route and controller
- Show followed categories
- Show "new this week" events per category
- Allow unfollowing

### Task 5: Implement User Category Digest
- Create CategoryDigestGenerator service
- Create weekly cron job to send digests
- Include boosted events prominently
- Include .ics links for each event

## Priority Order

1. **High Priority**: Boost products configuration and Views sorting
2. **Medium Priority**: Category following with Flag module
3. **Medium Priority**: My Categories page
4. **Low Priority**: User category digest (can be enhanced later)

