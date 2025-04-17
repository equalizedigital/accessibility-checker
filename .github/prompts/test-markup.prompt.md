I have a set of accessibility test cases. Can you generate WordPress block editor markup that I can paste into the editor which matches all these test cases?

Please follow these guidelines:

1. **Separate the test cases into two sections**:
   - One for tests expected to **pass**
   - One for tests expected to **fail**

2. **Use proper HTML hierarchy**:
   - Add an `<h2>` heading titled "Passing Tests" above the passing block markup.
   - Add an `<h2>` heading titled "Failing Tests" above the failing block markup.
   - Each test case should have its name included as an `<h3>` heading above it.

3. **Wrap the test markup inside a Gutenberg HTML block**:
   - Enclose each example in `<!-- wp:html -->` and `<!-- /wp:html -->` so it’s valid in Code Editor mode.
   - Do **not** modify the test markup—output it exactly as provided.

4. **Add a unique `data-test-name` attribute**:
   - For any element in the failing test case that will be flagged in a scan, add a `data-test-name="..."` attribute, using the test name or a simplified identifier.
   - This helps ensure the issue is clearly identifiable.

5. **Output only valid WordPress block markup**:
   - Structure it so I can copy and paste it directly into the block editor in "Code Editor" mode.

Optional:
- If possible, at the end of the output, summarize how many failing cases there are.