# Design Document: Menu Ordering System

## Overview

The Menu Ordering System is a full-stack feature that enables restaurant administrators to visually reorder their menu structure through drag-and-drop interactions. The system consists of a React-based frontend component using react-dnd for drag-and-drop functionality, and a Laravel backend API that processes and persists menu order changes atomically.

The system manages three entity types in a hierarchical structure:
- **Main Categories**: Top-level menu sections (e.g., "Appetizers", "Entrees")
- **Sub Categories**: Second-level groupings within Main Categories (e.g., "Hot Appetizers")
- **Items**: Individual menu items that belong to either Main Categories or Sub Categories

Each entity maintains a `menuOrderId` field (integer) that determines its display order. The system ensures that all order changes are validated, scoped to the correct business tenant, and persisted atomically to prevent inconsistent states.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Admin Dashboard (React)                   │
│  ┌────────────────────────────────────────────────────────┐ │
│  │         DraggableMenu Component                        │ │
│  │  - Drag-and-drop interface (react-dnd)                │ │
│  │  - Local state management                             │ │
│  │  - Visual feedback                                    │ │
│  └────────────────────────────────────────────────────────┘ │
│                           │                                  │
│                           │ HTTP POST                        │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────────┐ │
│  │         API Service Layer                              │ │
│  │  - JWT authentication                                  │ │
│  │  - Request formatting                                  │ │
│  │  - Error handling                                      │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                           │
                           │ HTTPS
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel API Backend                       │
│  ┌────────────────────────────────────────────────────────┐ │
│  │         Middleware Layer                               │ │
│  │  - validate.ui (origin validation)                     │ │
│  │  - auth:api (JWT verification)                         │ │
│  │  - verified (email verification)                       │ │
│  └────────────────────────────────────────────────────────┘ │
│                           │                                  │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────────┐ │
│  │         Controller Layer                               │ │
│  │  - MainCategoryController::updateMenuOrder()           │ │
│  │  - SubCategoryController::updateMenuOrder()            │ │
│  │  - ItemController::updateMenuOrder()                   │ │
│  └────────────────────────────────────────────────────────┘ │
│                           │                                  │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────────┐ │
│  │         Database Transaction Layer                     │ │
│  │  - DB::beginTransaction()                              │ │
│  │  - Batch UPDATE operations                             │ │
│  │  - DB::commit() / DB::rollBack()                       │ │
│  └────────────────────────────────────────────────────────┘ │
│                           │                                  │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────────┐ │
│  │         MySQL Database                                 │ │
│  │  - main_categories table                               │ │
│  │  - sub_categories table                                │ │
│  │  - items table                                         │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

1. **User Interaction**: User drags an entity to a new position in the DraggableMenu component
2. **Local State Update**: React component updates local state with new positions and recalculates menuOrderId values
3. **Save Trigger**: User clicks "Save Menu Order" button
4. **API Request**: Frontend sends POST request to `/{controller}/menu-order` with array of `{id, menuOrderId}` pairs
5. **Authentication**: API middleware validates JWT token and extracts user's businessId
6. **Transaction Start**: Controller begins database transaction
7. **Batch Update**: Controller iterates through updates and modifies menuOrderId for each entity
8. **Commit/Rollback**: Transaction commits on success or rolls back on any failure
9. **Response**: API returns success/error response
10. **User Feedback**: Frontend displays notification based on response

## Components and Interfaces

### Frontend Components

#### DraggableMenu Component

**Location**: `findmenu-admin/src/components/DraggableMenu.jsx`

**Props**:
- `businessId` (number): The ID of the current business tenant
- `controller` (string): The API endpoint name ("main-categories", "sub-categories", or "items")

**State**:
- `mainCategory` (array): Array of menu entities with their current order

**Key Methods**:
- `getMainCategory()`: Fetches entities from API filtered by businessId
- `moveItem(fromIndex, toIndex)`: Updates local state when user drags items
- `saveOrder()`: Sends updated order to API

**Dependencies**:
- `react-dnd`: Drag-and-drop functionality
- `react-dnd-html5-backend`: HTML5 drag-and-drop backend
- `antd`: UI components (Button, notification)
- `apiService`: HTTP client for API calls

#### DraggableItem Component

**Props**:
- `item` (object): The menu entity to render
- `index` (number): Current position in the list
- `moveItem` (function): Callback to update position

**Hooks**:
- `useDrag`: Provides drag source functionality
- `useDrop`: Provides drop target functionality

### Backend API Endpoints

#### POST /{controller}/menu-order

**Controllers**:
- `MainCategoryController::updateMenuOrder()`
- `SubCategoryController::updateMenuOrder()`
- `ItemController::updateMenuOrder()`

**Authentication**: Required (JWT via `auth:api` middleware)

**Request Body**:
```json
{
  "updateData": [
    { "id": 1, "menuOrderId": 1 },
    { "id": 2, "menuOrderId": 2 },
    { "id": 3, "menuOrderId": 3 }
  ]
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": [],
  "message": "Menu order updated successfully"
}
```

**Error Response** (500):
```json
{
  "success": false,
  "message": "Failed to update menu order",
  "data": "Error details"
}
```

**Validation**:
- Each item in `updateData` must have `id` and `menuOrderId` fields
- All IDs must exist in the respective table
- All entities must belong to the authenticated user's business

#### GET /{controller}

**Purpose**: Fetch entities for display in drag-and-drop interface

**Query Parameters**:
- `businessId` (optional): Filtered automatically for authenticated users

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Appetizers",
      "menuOrderId": 1,
      "businessId": 5,
      "status": 1,
      "isAvailable": 1
    }
  ]
}
```

**Ordering**: Results are always ordered by `menuOrderId ASC`

### API Service Layer

**Location**: `findmenu-admin/src/services/apiService.js`

**Key Features**:
- Axios instance with base URL configuration
- JWT token injection via interceptors
- Automatic token refresh on 401 responses
- Request/response logging
- Error handling and user logout on auth failure

**Methods Used**:
- `apiService.get(url, params)`: Fetch entities
- `apiService.post(url, data)`: Save menu order

## Data Models

### MainCategory Model

**Table**: `main_categories`

**Key Fields**:
- `id` (integer, primary key)
- `name` (string, max 100)
- `description` (text, nullable)
- `image` (string, nullable)
- `status` (integer, 1=active, 0=inactive)
- `isAvailable` (integer, 1=available, 0=unavailable)
- `menuOrderId` (integer, determines display order)
- `businessId` (integer, foreign key to businesses)
- `deleted_at` (timestamp, soft delete)

**Relationships**:
- `belongsTo(Business)`
- `hasMany(SubCategory)`

**Unique Constraint**: `name` must be unique within a `businessId`

### SubCategory Model

**Table**: `sub_categories`

**Key Fields**:
- `id` (integer, primary key)
- `name` (string, max 100)
- `description` (text, nullable)
- `image` (string, nullable)
- `status` (integer)
- `isAvailable` (integer)
- `menuOrderId` (integer, determines display order)
- `businessId` (integer, foreign key to businesses)
- `categoryId` (integer, foreign key to main_categories)
- `deleted_at` (timestamp, soft delete)

**Relationships**:
- `belongsTo(Business)`
- `belongsTo(MainCategory)`
- `hasMany(Item)`

**Unique Constraint**: `name` must be unique within a `businessId` and `categoryId` combination

### Item Model

**Table**: `items`

**Key Fields**:
- `id` (integer, primary key)
- `name` (string, max 100)
- `description` (text, nullable)
- `image` (string, nullable)
- `price` (decimal, nullable)
- `status` (integer)
- `isAvailable` (integer)
- `menuOrderId` (integer, determines display order)
- `businessId` (integer, foreign key to businesses)
- `categoryId` (integer, foreign key to main_categories)
- `subCategoryId` (integer, foreign key to sub_categories, nullable)
- `deleted_at` (timestamp, soft delete)

**Relationships**:
- `belongsTo(Business)`
- `belongsTo(MainCategory)`
- `belongsTo(SubCategory)` (optional)

**Unique Constraint**: `name` must be unique within a `businessId` and `categoryId` combination

### Database Indexes

For optimal performance, the following indexes should exist:
- `main_categories(businessId, menuOrderId)`
- `sub_categories(businessId, categoryId, menuOrderId)`
- `items(businessId, categoryId, menuOrderId)`
- `items(businessId, subCategoryId, menuOrderId)`


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

After analyzing all acceptance criteria, several properties were identified as redundant across the three entity types (Main Categories, Sub Categories, and Items). Since the menu ordering logic is identical for all entity types, we consolidate these into unified properties that apply to any menu entity type.

### Property 1: Drag operation updates local state

*For any* menu entity list and any valid drag operation (moving an entity from position A to position B), the local component state should immediately reflect the new order without waiting for server confirmation.

**Validates: Requirements 1.1, 2.1, 3.1**

### Property 2: MenuOrderId recalculation correctness

*For any* list of menu entities after a reorder operation, the menuOrderId values should equal the entity's position in the array (index + 1), ensuring sequential ordering starting from 1.

**Validates: Requirements 1.2, 2.2, 3.2**

### Property 3: Save request payload completeness

*For any* save operation, the API request payload should contain all entities from the current list, each with both `id` and `menuOrderId` fields present.

**Validates: Requirements 1.3, 2.3, 3.3**

### Property 4: Transaction atomicity

*For any* menu order update operation, either all menuOrderId changes are persisted to the database or none are persisted, with no partial updates possible.

**Validates: Requirements 1.4, 1.5, 2.4, 2.5, 3.4, 3.5, 8.3, 8.4**

### Property 5: Required field validation

*For any* menu order update request, if any entity in the payload is missing either the `id` or `menuOrderId` field, the API should reject the entire request with a validation error.

**Validates: Requirements 5.1, 5.2**

### Property 6: Entity existence validation

*For any* menu order update request, if any entity ID does not exist in the database, the API should reject the request with an error before attempting any updates.

**Validates: Requirements 5.3, 5.4**

### Property 7: Success response format

*For any* successful menu order save operation, the API should return an HTTP 200 status code with a success message.

**Validates: Requirements 6.1**

### Property 8: Frontend success notification

*For any* successful API response (status 200), the frontend should display a success notification to the user.

**Validates: Requirements 6.2**

### Property 9: Error response format

*For any* failed menu order save operation, the API should return an HTTP 500 status code with error details.

**Validates: Requirements 6.3**

### Property 10: Frontend error notification

*For any* error API response (status 500 or network error), the frontend should display an error notification with details to the user.

**Validates: Requirements 6.4, 6.5**

### Property 11: Business-scoped data retrieval

*For any* authenticated user requesting menu entities, the API should return only entities where the businessId matches the authenticated user's businessId.

**Validates: Requirements 7.1, 7.4**

### Property 12: Cross-business update prevention

*For any* menu order update request, if any entity in the payload belongs to a different businessId than the authenticated user's businessId, the API should reject the entire request with an authorization error.

**Validates: Requirements 7.2, 7.3, 7.5**

### Property 13: Query result ordering

*For any* API request that retrieves menu entities (Main Categories, Sub Categories, or Items), the results should be ordered by menuOrderId in ascending order.

**Validates: Requirements 9.1, 9.2, 9.3**

### Property 14: Complete menu hierarchy ordering

*For any* complete menu structure response, all hierarchy levels (categories, subcategories, and items) should be ordered by their respective menuOrderId values in ascending order.

**Validates: Requirements 9.4**

### Property 15: Save-then-fetch consistency (Round-trip)

*For any* menu order update that is successfully saved, immediately fetching the same entities should return them in the newly saved order.

**Validates: Requirements 9.5**

## Error Handling

### Frontend Error Handling

**Drag-and-Drop Errors**:
- If drag operation fails to update local state, log error to console and maintain previous state
- Display user-friendly message if drag-and-drop library fails to initialize

**API Request Errors**:
- **Network Errors**: Display "Unable to connect to server. Please check your internet connection."
- **401 Unauthorized**: Trigger automatic token refresh, retry request once, then redirect to login if still failing
- **403 Forbidden**: Display "You don't have permission to modify this menu."
- **500 Server Error**: Display "Failed to save menu order. Please try again." with error details
- **Timeout**: Display "Request timed out. Please try again."

**Validation Errors**:
- Display specific validation messages returned from API
- Highlight problematic entities in the UI if possible

### Backend Error Handling

**Validation Errors**:
- Return 422 status code with detailed validation error messages
- Include field-level errors in response for frontend to display

**Database Errors**:
- Catch all database exceptions within transaction
- Roll back transaction on any error
- Log full error details with stack trace
- Return generic 500 error to client (don't expose internal details)

**Authorization Errors**:
- Return 403 status code when user attempts to modify entities from another business
- Log security violations for audit purposes

**Transaction Failures**:
- Ensure `DB::rollBack()` is called in catch blocks
- Log transaction failures with context (user ID, business ID, entity IDs)
- Return 500 status code with message "Failed to update menu order"

**Missing Entity Errors**:
- Return 404 status code when entity ID doesn't exist
- Include which IDs were not found in error message

## Testing Strategy

### Unit Testing

**Frontend Unit Tests** (Jest + React Testing Library):

1. **DraggableMenu Component Tests**:
   - Test that component renders with correct initial state
   - Test that `getMainCategory()` is called on mount
   - Test that `moveItem()` correctly updates local state
   - Test that `saveOrder()` calls API service with correct payload
   - Test error handling when API call fails

2. **DraggableItem Component Tests**:
   - Test that drag source is properly configured
   - Test that drop target is properly configured
   - Test that `moveItem` callback is invoked on hover

3. **API Service Tests**:
   - Test that JWT token is included in request headers
   - Test that 401 responses trigger token refresh
   - Test that network errors are properly caught and handled

**Backend Unit Tests** (PHPUnit):

1. **Controller Tests**:
   - Test `updateMenuOrder()` with valid data returns 200
   - Test `updateMenuOrder()` with missing fields returns validation error
   - Test `updateMenuOrder()` with non-existent IDs returns error
   - Test `updateMenuOrder()` with cross-business IDs returns 403
   - Test transaction rollback on database error

2. **Model Tests**:
   - Test that entities are ordered by menuOrderId by default
   - Test that soft deletes work correctly
   - Test business relationship filtering

3. **Middleware Tests**:
   - Test that unauthenticated requests are rejected
   - Test that JWT token is properly validated
   - Test that businessId is correctly extracted from token

### Property-Based Testing

We will use **fast-check** for JavaScript/TypeScript property tests and **Pest with Faker** for PHP property tests.

**Configuration**: Each property-based test should run a minimum of 100 iterations to ensure adequate coverage of the input space.

**Frontend Property Tests**:

1. **Property 2: MenuOrderId Recalculation**
   - Generate: Random arrays of menu entities with random initial menuOrderId values
   - Operation: Call recalculation logic
   - Assert: Resulting menuOrderId values equal index + 1

2. **Property 3: Save Payload Completeness**
   - Generate: Random arrays of menu entities
   - Operation: Build save payload
   - Assert: Payload contains all entities with id and menuOrderId fields

**Backend Property Tests**:

1. **Property 4: Transaction Atomicity**
   - Generate: Random arrays of valid menu order updates, inject one invalid update
   - Operation: Call updateMenuOrder()
   - Assert: Database state unchanged (all or nothing)

2. **Property 5: Required Field Validation**
   - Generate: Random payloads with some entities missing id or menuOrderId
   - Operation: Call updateMenuOrder()
   - Assert: Request rejected with validation error

3. **Property 6: Entity Existence Validation**
   - Generate: Random payloads with some non-existent entity IDs
   - Operation: Call updateMenuOrder()
   - Assert: Request rejected with error

4. **Property 11: Business-Scoped Retrieval**
   - Generate: Random businessId and database with entities from multiple businesses
   - Operation: Call index() with authenticated user
   - Assert: All returned entities have matching businessId

5. **Property 12: Cross-Business Update Prevention**
   - Generate: Random payload with entities from different businessId
   - Operation: Call updateMenuOrder() with authenticated user
   - Assert: Request rejected with authorization error

6. **Property 13: Query Result Ordering**
   - Generate: Random entities with random menuOrderId values
   - Operation: Call index()
   - Assert: Results ordered by menuOrderId ascending

7. **Property 15: Save-Then-Fetch Consistency**
   - Generate: Random menu order updates
   - Operation: Save via updateMenuOrder(), then fetch via index()
   - Assert: Fetched order matches saved order

### Integration Testing

1. **End-to-End Drag-and-Drop Flow**:
   - Start with known menu structure
   - Simulate drag operation in frontend
   - Verify API request is sent with correct data
   - Verify database is updated correctly
   - Verify subsequent fetch returns new order

2. **Multi-User Concurrency**:
   - Simulate two users from different businesses updating menu order simultaneously
   - Verify each user's changes are isolated to their business
   - Verify no cross-contamination of data

3. **Transaction Rollback Scenario**:
   - Create scenario where database update will fail mid-transaction
   - Verify no partial updates are persisted
   - Verify error is returned to frontend

### Test Data Generators

**Frontend Generators**:
```javascript
// Generate random menu entity
const generateEntity = () => ({
  id: fc.integer({ min: 1, max: 1000 }),
  name: fc.string({ minLength: 1, maxLength: 100 }),
  menuOrderId: fc.integer({ min: 1, max: 100 }),
  businessId: fc.integer({ min: 1, max: 50 }),
  status: fc.constantFrom(0, 1),
  isAvailable: fc.constantFrom(0, 1)
});

// Generate array of entities
const generateEntityList = () => fc.array(generateEntity(), { minLength: 1, maxLength: 20 });
```

**Backend Generators**:
```php
// Generate random menu entity
function generateEntity(): array {
    return [
        'id' => fake()->numberBetween(1, 1000),
        'name' => fake()->words(3, true),
        'menuOrderId' => fake()->numberBetween(1, 100),
        'businessId' => fake()->numberBetween(1, 50),
        'status' => fake()->randomElement([0, 1]),
        'isAvailable' => fake()->randomElement([0, 1]),
    ];
}

// Generate array of entities
function generateEntityList(int $count = null): array {
    $count = $count ?? fake()->numberBetween(1, 20);
    return array_map(fn() => generateEntity(), range(1, $count));
}
```

## Performance Considerations

### Frontend Performance

**Drag-and-Drop Optimization**:
- Use React.memo() for DraggableItem to prevent unnecessary re-renders
- Debounce hover events to reduce state updates during drag
- Limit list size to 100 items per view (paginate if needed)

**State Management**:
- Keep drag state local to component (don't use Redux for drag operations)
- Only update Redux/global state after successful save

### Backend Performance

**Database Optimization**:
- Use batch UPDATE with single query instead of loop (if possible)
- Ensure indexes exist on (businessId, menuOrderId) for fast ordering
- Use database transactions to minimize lock time

**Query Optimization**:
- Always include businessId in WHERE clause to use indexes
- Limit result sets with pagination for large menus
- Use SELECT only needed columns (avoid SELECT *)

**Caching Strategy**:
- Cache complete menu structure for each business (invalidate on order change)
- Use Redis for menu cache with TTL of 1 hour
- Cache key format: `menu:complete:{businessId}`

### Scalability Considerations

**Concurrent Updates**:
- Use optimistic locking if multiple admins can edit simultaneously
- Consider row-level locking for high-concurrency scenarios
- Implement retry logic with exponential backoff

**Large Menus**:
- For menus with >100 items, implement virtual scrolling in frontend
- Consider breaking large menus into multiple pages
- Implement lazy loading for subcategories and items

## Security Considerations

### Authentication & Authorization

**JWT Token Security**:
- Tokens expire after configurable time (default 1 hour)
- Refresh tokens used to obtain new access tokens
- Tokens encrypted before storage in localStorage

**Business Isolation**:
- All queries filtered by authenticated user's businessId
- Cross-business access attempts logged as security events
- API validates businessId on every request

### Input Validation

**Frontend Validation**:
- Validate entity structure before sending to API
- Sanitize user input (though menu ordering doesn't accept user text input)
- Validate array lengths to prevent oversized payloads

**Backend Validation**:
- Validate all required fields present
- Validate data types (id and menuOrderId must be integers)
- Validate entity IDs exist in database
- Validate businessId matches authenticated user
- Limit array size to prevent DoS attacks (max 1000 entities per request)

### SQL Injection Prevention

- Use Laravel's query builder with parameter binding (already implemented)
- Never concatenate user input into SQL queries
- Validate all IDs are integers before using in queries

### CSRF Protection

- API uses JWT authentication (stateless, no CSRF vulnerability)
- validate.ui middleware ensures requests come from authorized origins
- X-Requested-With header required for AJAX requests

## Deployment Considerations

### Database Migrations

No new migrations required - the system uses existing tables with existing `menuOrderId` columns.

**Verify Existing Schema**:
```sql
-- Verify menuOrderId column exists
DESCRIBE main_categories;
DESCRIBE sub_categories;
DESCRIBE items;

-- Verify indexes exist
SHOW INDEX FROM main_categories WHERE Column_name = 'menuOrderId';
SHOW INDEX FROM sub_categories WHERE Column_name = 'menuOrderId';
SHOW INDEX FROM items WHERE Column_name = 'menuOrderId';
```

**Add Indexes if Missing**:
```sql
ALTER TABLE main_categories ADD INDEX idx_business_order (businessId, menuOrderId);
ALTER TABLE sub_categories ADD INDEX idx_business_category_order (businessId, categoryId, menuOrderId);
ALTER TABLE items ADD INDEX idx_business_category_order (businessId, categoryId, menuOrderId);
ALTER TABLE items ADD INDEX idx_business_subcat_order (businessId, subCategoryId, menuOrderId);
```

### Environment Configuration

No new environment variables required. System uses existing configuration:
- `VITE_API_URL`: API base URL (frontend)
- `API_URL`: API URL (backend)
- `DB_*`: Database connection settings

### Rollout Strategy

**Phase 1: Backend Deployment**
1. Deploy API changes to staging environment
2. Run integration tests against staging
3. Deploy to production during low-traffic window
4. Monitor error logs for 24 hours

**Phase 2: Frontend Deployment**
1. Deploy frontend changes to staging
2. Test drag-and-drop functionality end-to-end
3. Deploy to production
4. Monitor user feedback and error reports

**Rollback Plan**:
- Backend: Revert to previous API version (no database changes needed)
- Frontend: Revert to previous build (no data loss)
- Database: No rollback needed (schema unchanged)

### Monitoring & Logging

**Backend Logging**:
- Log all menu order update requests with user ID and business ID
- Log transaction failures with full error details
- Log authorization failures (cross-business access attempts)
- Log performance metrics (transaction duration)

**Frontend Logging**:
- Log API errors to console (development) or error tracking service (production)
- Track drag-and-drop usage metrics (how often feature is used)
- Monitor API response times

**Alerts**:
- Alert on high rate of 500 errors from menu order endpoints
- Alert on transaction rollback rate >5%
- Alert on authorization failures (potential security issue)

## Future Enhancements

### Potential Improvements

1. **Undo/Redo Functionality**:
   - Maintain history of menu order changes
   - Allow users to undo recent changes
   - Implement with command pattern

2. **Bulk Operations**:
   - Allow reordering multiple entity types in single save
   - Implement "Reset to Alphabetical Order" feature
   - Add "Move to Top/Bottom" quick actions

3. **Real-Time Collaboration**:
   - Use WebSockets to show when other admins are editing
   - Implement conflict resolution for simultaneous edits
   - Show live cursor positions during drag operations

4. **Drag Between Categories**:
   - Allow dragging items between categories
   - Allow dragging subcategories between main categories
   - Update parent relationships automatically

5. **Touch Device Support**:
   - Implement touch-friendly drag-and-drop
   - Add mobile-specific UI for reordering
   - Test on tablets and smartphones

6. **Accessibility Improvements**:
   - Add keyboard navigation for reordering
   - Implement screen reader announcements
   - Add ARIA labels for drag-and-drop elements

7. **Performance Optimization**:
   - Implement virtual scrolling for large lists
   - Add optimistic UI updates (show success before API confirms)
   - Batch multiple rapid changes into single API call
