# task-refactor
Taking legacy code from a project and task manager and refactoring. 

I've been wanting to implement the refactoring techniques I've been learning, so I decided to JUST DO IT! I'll be following "Modernizing Legacy Applications" by Paul M Jones https://leanpub.com/mlaphp. I'll be adding my own notes as I go with tips or issues I run into.

## Roadmap for Refactor

1. Inital file setup
    1. make files work as a standalone project
    2. include sql import for tables
2. Layout style guides and other standards
3. Implement An Autoloader
    1. PSR-0
    2. A Single Location For Classes
    3. Add Autoloader Code
    4. Common Questions
    5. Review and Next Steps
4. Consolidate Classes and Functions
    1. Consolidate Class Files
    2. Consolidate Functions Into Class Files
    3. Common Questions
    4. Review and Next Steps
5. Replace global With Dependency Injection
    1. Global Dependencies
    2. The Replacement Process
    3. Common Questions
    4. Review and Next Steps
6. Replace new With Dependency Injection
    1. Embedded Instantiation
    2. The Replacement Process
    3. Common Questions
    4. Review and Next Steps
7. Write Tests
    1. Fighting Test Resistance
    2. Setting Up A Test Suite
    3. Common Questions
    4. Review and Next Steps
8. Extract SQL Statements To Gateways
    1. Embedded SQL Statements
    2. The Extraction Process
    3. Common Questions
    4. Review and Next Steps
9. Extract Domain Logic To Transactions
    1. Embedded Domain Logic
    2. Domain Logic Patterns
    3. The Extraction Process
    4. Common Questions
    5. Review and Next Steps
10. Extract Presentation Logic To View Files
    1. Embedded Presentation Logic
    2. The Extraction Process
    3. Common Questions
    4. Review and Next Steps
11. Extract Action Logic To Controllers
    1. Embedded Action Logic
    2. The Extraction Process
    3. Common Questions
    4. Review and Next Steps
12. Replace Includes In Classes
    1. Embedded include Calls
    2. The Replacement Process
    3. Common Questions
    4. Review and Next Steps
13. Separate Public And Non-Public Resources
    1. Intermingled Resources
    2. The Separation Process
    3. Common Questions
    4. Review and Next Steps
14. Decouple URL Paths From File Paths
    1. Coupled Paths
    2. The Decoupling Process
    3. Common Questions
    4. Review and Next Steps
15. Remove Repeated Logic In Page Scripts
    1. Repeated Logic
    2. The Removal Provess
    3. Common Questions
    4. Review and Next Steps
16. Add A Dependency Injection Container
    1. What Is A Dependency Injection Container?
    2. Adding A DI Container
    3. Common Questions
    4. Review and Next Steps
17. Conclusion
    1. Opportunities for Improvement
    2. Conversion to Framework
    3. Review and Next Steps
