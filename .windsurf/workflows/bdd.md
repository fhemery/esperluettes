---
description: Workflow to work in behavior driven development
auto_execution_mode: 1
---

When entering BDD state, we are going to implement the feature slice by slice. The feature should already have been clearly reviewed and questions asked.

1. Look at the feature described and ask any question regarding the feature slice
2. Write one or more failing test cases to cover the feature slice. 
3. Run the test, and ensure they are failing for the right reason. If tests are passing, check this is normal.

If all tests are passing, the slice iteration stops here and you can ask for the next iteration. Else:

4. Implement to code enabling to pass the tests. Write the code in the correct files (we will not spend our time refactoring once tests pass)
5. Run the tests again, and check they are green
6. Report any architectural, technical or functional choice you have made during implementation and requires my acknowledgement or review
7. Ask for the next slice until I tell you we're done.