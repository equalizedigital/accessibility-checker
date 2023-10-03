

const updatePostFlow = async (page, postId, html, issueSlug, issueCount) => {

  await page.goto('/wp-admin/post.php?post=' + postId + '&action=edit');

  await page.getByPlaceholder('Write HTML…').fill(html);

  // Update.
  await page.getByRole('button', { name: 'Update', exact: true }).click();

  //Check details after publish.
  await page.getByRole('button', { name: 'Details' }).click();
  const detailItem1 = page.locator('div').filter({ hasText: new RegExp(issueCount + ' ' + issueSlug + '$') })
  const detailCountItem1 = detailItem1.locator('.edac-details-rule-count');
  const detailCount1 = await detailCountItem1.innerText();


  //Check summary after publish.
  await page.getByRole('button', { name: 'Summary' }).click();
  const summaryItem1 = await page.getByText(issueCount + ' Error');
  const summaryCountItem1 = summaryItem1.locator('.edac-panel-number');
  const summaryCount1 = await summaryCountItem1.innerText();

  return (
    parseInt(detailCount1) == issueCount && parseInt(summaryCount1) == issueCount
  );

};


export default updatePostFlow;
