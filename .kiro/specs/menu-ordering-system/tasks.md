# Implementation Plan: Menu Ordering System

## Overview

This implementation plan breaks down the Menu Ordering System into discrete, manageable tasks that build incrementally. The plan follows an implementation-first approach where core functionality is built before tests, and focuses on code that can be executed by a development agent.

## Task List

- [x] 1. Verify and document existing backend infrastructure



  - Review existing `updateMenuOrder()` methods in MainCategoryController, SubCategoryController, and ItemController
  - Verify database schema has `menuOrderId` columns on all three tables
  - Check for existing indexes on (businessId, menuOrderId) combinations
  - Document any gaps or issues found





  - _Requirements: 1.4, 8.1, 8.2_

- [ ] 2. Enhance backend menu order update endpoints
  - [x] 2.1 Add comprehensive input validation to all three controllers


    - Validate that `updateData` array exists in request
    - Validate each item has both `id` and `menuOrderId` fields
    - Return 422 status with detailed errors for validation failures
    - _Requirements: 5.1, 5.2_



  - [ ] 2.2 Add entity existence validation
    - Before transaction, verify all entity IDs exist in database
    - Return 404 error if any ID not found
    - Include list of missing IDs in error response

    - _Requirements: 5.3, 5.4_


  - [ ] 2.3 Add business authorization checks
    - Fetch all entities by ID before updating
    - Verify each entity's businessId matches authenticated user's businessId
    - Return 403 error if any entity belongs to different business
    - Log authorization failures for security audit
    - _Requirements: 7.2, 7.3, 7.5_

  - [ ] 2.4 Improve error handling and logging
    - Add try-catch around transaction logic
    - Log transaction start, success, and failure events
    - Include user ID, business ID, and entity count in logs
    - Return consistent error response format
    - _Requirements: 1.5, 6.3, 8.5_

  - [ ]* 2.5 Write property test for transaction atomicity
    - **Property 4: Transaction atomicity**
    - **Validates: Requirements 1.4, 1.5, 8.3, 8.4**
    - Generate random valid updates with one invalid update injected
    - Verify database state unchanged after failed transaction
    - Test with all three entity types (MainCategory, SubCategory, Item)

  - [ ]* 2.6 Write property test for required field validation
    - **Property 5: Required field validation**
    - **Validates: Requirements 5.1, 5.2**
    - Generate random payloads with missing id or menuOrderId fields
    - Verify API rejects with 422 validation error
    - Test with all three entity types

  - [x]* 2.7 Write property test for entity existence validation




    - **Property 6: Entity existence validation**
    - **Validates: Requirements 5.3, 5.4**
    - Generate payloads with non-existent entity IDs
    - Verify API rejects with 404 error
    - Test with all three entity types


  - [ ]* 2.8 Write property test for cross-business update prevention
    - **Property 12: Cross-business update prevention**
    - **Validates: Requirements 7.2, 7.3, 7.5**
    - Create entities for multiple businesses
    - Attempt to update entities from different business
    - Verify API rejects with 403 authorization error

- [ ] 3. Enhance backend entity retrieval endpoints
  - [ ] 3.1 Verify business-scoped filtering in index() methods
    - Ensure authenticated users only see their business's entities
    - Verify businessId filter is applied in all three controllers
    - Test with multiple businesses in database
    - _Requirements: 7.1, 7.4_

  - [ ] 3.2 Verify menuOrderId ordering in queries
    - Ensure all index() methods order by menuOrderId ASC
    - Verify ordering is consistent across all three entity types
    - Test with entities having random menuOrderId values





    - _Requirements: 9.1, 9.2, 9.3_

  - [ ]* 3.3 Write property test for business-scoped retrieval
    - **Property 11: Business-scoped data retrieval**
    - **Validates: Requirements 7.1, 7.4**
    - Create entities for multiple businesses
    - Authenticate as user from specific business
    - Verify only that business's entities are returned
    - Test with all three entity types

  - [ ]* 3.4 Write property test for query result ordering
    - **Property 13: Query result ordering**
    - **Validates: Requirements 9.1, 9.2, 9.3**
    - Create entities with random menuOrderId values
    - Fetch via index() endpoint
    - Verify results ordered by menuOrderId ascending
    - Test with all three entity types





- [ ] 4. Enhance complete menu endpoint
  - [ ] 4.1 Verify hierarchy ordering in MenuController::getCompleteMenu()
    - Ensure categories ordered by menuOrderId
    - Ensure subcategories within each category ordered by menuOrderId
    - Ensure items within each category/subcategory ordered by menuOrderId

    - _Requirements: 9.4_

  - [ ]* 4.2 Write property test for complete menu hierarchy ordering
    - **Property 14: Complete menu hierarchy ordering**
    - **Validates: Requirements 9.4**
    - Create random menu structure with random menuOrderId values

    - Fetch complete menu
    - Verify all levels ordered by menuOrderId ascending

  - [ ]* 4.3 Write property test for save-then-fetch consistency
    - **Property 15: Save-then-fetch consistency**

    - **Validates: Requirements 9.5**
    - Generate random menu order updates
    - Save via updateMenuOrder()
    - Immediately fetch via index()
    - Verify fetched order matches saved order

    - Test with all three entity types

- [ ] 5. Create reusable DraggableMenu component
  - [ ] 5.1 Implement base DraggableMenu component
    - Create component accepting businessId and controller props
    - Implement state management for entity list
    - Add useEffect to fetch entities on mount
    - Filter entities by businessId
    - _Requirements: 1.1, 7.1_

  - [ ] 5.2 Implement drag-and-drop functionality
    - Set up DndProvider with HTML5Backend
    - Create DraggableItem sub-component with useDrag and useDrop hooks
    - Implement moveItem() function to update local state on drag
    - Ensure immediate visual feedback during drag operations
    - _Requirements: 1.1, 4.1, 4.2, 4.3, 4.4_

  - [ ] 5.3 Implement menuOrderId recalculation logic
    - Create function to recalculate menuOrderId based on array position
    - Call recalculation after each moveItem() operation
    - Ensure menuOrderId values are sequential starting from 1
    - _Requirements: 1.2_

  - [ ] 5.4 Implement save functionality
    - Create saveOrder() function to send updates to API
    - Format payload as { updateData: [{ id, menuOrderId }] }
    - Call appropriate API endpoint based on controller prop



    - _Requirements: 1.3_


  - [ ] 5.5 Implement success/error notifications
    - Use Ant Design notification API
    - Display success notification on 200 response
    - Display error notification on error response or network failure

    - Include error details in error notifications
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [ ]* 5.6 Write property test for menuOrderId recalculation
    - **Property 2: MenuOrderId recalculation correctness**
    - **Validates: Requirements 1.2, 2.2, 3.2**
    - Generate random entity arrays with random initial menuOrderId values

    - Call recalculation logic
    - Verify resulting menuOrderId equals index + 1




  - [-]* 5.7 Write property test for save payload completeness

    - **Property 3: Save request payload completeness**
    - **Validates: Requirements 1.3, 2.3, 3.3**
    - Generate random entity arrays
    - Build save payload
    - Verify payload contains all entities with id and menuOrderId fields


  - [ ]* 5.8 Write unit tests for DraggableMenu component
    - Test component renders with initial state


    - Test getMainCategory() called on mount
    - Test moveItem() updates local state correctly
    - Test saveOrder() calls API with correct payload
    - Test error handling when API call fails
    - Test success/error notifications displayed correctly

- [ ] 6. Integrate DraggableMenu into existing admin pages
  - [ ] 6.1 Update MainCategory page to use DraggableMenu
    - Import DraggableMenu component
    - Pass businessId and "main-categories" as controller prop
    - Replace or enhance existing menu ordering UI
    - Test drag-and-drop functionality
    - _Requirements: 1.1, 1.2, 1.3_

  - [ ] 6.2 Update SubCategory page to use DraggableMenu
    - Import DraggableMenu component
    - Pass businessId and "sub-categories" as controller prop
    - Replace or enhance existing menu ordering UI
    - Test drag-and-drop functionality
    - _Requirements: 2.1, 2.2, 2.3_

  - [ ] 6.3 Update Items page to use DraggableMenu
    - Import DraggableMenu component
    - Pass businessId and "items" as controller prop
    - Replace or enhance existing menu ordering UI
    - Test drag-and-drop functionality
    - _Requirements: 3.1, 3.2, 3.3_

- [ ] 7. Add database indexes for performance
  - [ ] 7.1 Create migration for menu ordering indexes
    - Add index on main_categories(businessId, menuOrderId)
    - Add index on sub_categories(businessId, categoryId, menuOrderId)
    - Add index on items(businessId, categoryId, menuOrderId)
    - Add index on items(businessId, subCategoryId, menuOrderId)
    - Make migration reversible with down() method

  - [ ] 7.2 Run migration in development environment
    - Execute migration
    - Verify indexes created successfully
    - Test query performance with EXPLAIN
    - Document index usage

- [ ] 8. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ]* 9. Add integration tests for end-to-end flows
  - [ ]* 9.1 Write integration test for complete drag-and-drop flow
    - Create test business with known menu structure
    - Simulate drag operation in frontend
    - Verify API request sent with correct data
    - Verify database updated correctly
    - Verify subsequent fetch returns new order

  - [ ]* 9.2 Write integration test for multi-business isolation
    - Create two businesses with menu entities
    - Authenticate as user from business A
    - Attempt to update entities from business B
    - Verify request rejected with 403 error
    - Verify business A's data unchanged

  - [ ]* 9.3 Write integration test for transaction rollback
    - Create scenario where database update will fail
    - Attempt menu order update
    - Verify no partial updates persisted
    - Verify error returned to frontend
    - Verify database state unchanged

- [ ] 10. Documentation and deployment preparation
  - [ ] 10.1 Update API documentation
    - Document enhanced validation in Swagger annotations
    - Add examples of error responses
    - Document business authorization requirements
    - Update endpoint descriptions

  - [ ] 10.2 Create deployment checklist
    - List database migrations to run
    - List environment variables to verify
    - List monitoring alerts to configure
    - Document rollback procedure

  - [ ] 10.3 Write user documentation
    - Create guide for using drag-and-drop menu ordering
    - Add screenshots of UI
    - Document common issues and solutions
    - Add FAQ section

- [ ] 11. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
