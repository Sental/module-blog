# What the storefront looks like

Every page this module renders, on the stock **Luma** theme. Captured from a clean install with
sample content: twelve published posts, one author, one category, two tags, and one post linked
to a product.

Use this as the reference when reviewing a change — if a page stops looking like its screenshot,
something regressed.

> On a Hyvä theme these same pages render through
> [`mage-os/module-blog-hyva`](https://github.com/mage-os/module-blog-hyva), which carries a
> Tailwind port of this stylesheet under the same class names. See its `docs/storefront.md` for
> the Hyvä equivalents of everything below.

---

## Blog index

`/blog` — posts as cards, capped at `mageos_blog/post/posts_per_page` (default 10), with
pagination below when there are more.

![Blog index](screenshots/desktop-01-blog-index.png)

Page two, reached from the pagination:

![Blog index page two](screenshots/desktop-02-blog-index-page2.png)

## Post detail

`/blog/<url-key>/` — hero image when the post has one, title, byline, the post body, then tags
and related posts in the footer.

The body is admin-authored HTML, so the stylesheet has to handle whatever the editor produces.
The sample below deliberately contains a second- and third-level heading, both list types, a
blockquote, inline and block code, a table and a horizontal rule.

![Post detail with a rich body](screenshots/desktop-03-post-rich-body.png)

A post with a plain body, for comparison:

![Post detail with a plain body](screenshots/desktop-04-post-plain.png)

## Category, tag and author

All three share the context header — title, optional description, then the same cards as the
index.

| | |
| --- | --- |
| `/blog/category/<url-key>/` | ![Category page](screenshots/desktop-05-category.png) |
| `/blog/tag/<url-key>/` | ![Tag page](screenshots/desktop-06-tag.png) |

The author page adds the avatar and the contact links row:

![Author page](screenshots/desktop-07-author.png)

## Search

`/blog/search/?q=…` — heading, result count, cards, pagination.

![Search results](screenshots/desktop-08-search-results.png)

And with no matches:

![Search with no results](screenshots/desktop-09-search-empty.png)

> Results currently have no stable secondary sort, so posts of equal relevance can come back in a
> different order between requests. Expect this page to reshuffle on refresh.

## Related posts on a product page

Gated behind `mageos_blog/related_posts/enabled`, which is **off by default**. When on, posts
linked to the product through the post form's Related Products field appear as a tab in the
product information area, titled from `mageos_blog/related_posts/title`.

![Product page with related posts](screenshots/desktop-10-pdp-with-related-posts.png)

A product with no linked posts shows **nothing** — no empty tab, no heading. The template emits
no markup at all rather than an empty wrapper, so Luma's blank-output check drops the tab
entirely. Worth re-checking whenever `product/related-posts.phtml` changes, because an empty
wrapper produces a titled, empty tab on every product in the catalogue:

![Product page with no related posts](screenshots/desktop-11-pdp-without-related-posts.png)

## Mobile

The same pages at 420px.

| | | |
| --- | --- | --- |
| Index | Post | Category |
| ![Index on mobile](screenshots/mobile-01-blog-index.png) | ![Post on mobile](screenshots/mobile-03-post-rich-body.png) | ![Category on mobile](screenshots/mobile-05-category.png) |
| Tag | Author | Search |
| ![Tag on mobile](screenshots/mobile-06-tag.png) | ![Author on mobile](screenshots/mobile-07-author.png) | ![Search on mobile](screenshots/mobile-08-search-results.png) |

---

## A note on sizing

The type scale in `blog-cards.css` is expressed in `em`, not `rem`, and deliberately so.

Luma sets `html { font-size: 62.5% }`, making `1rem` equal 10px where most themes leave it at
16px. A rem-based scale therefore rendered the whole blog at 62.5% on Luma — body copy around
11px, smaller than any other text on the page, which made it look like a foreign object dropped
into the theme.

Sizing against the **inherited** body copy instead lets the blog adapt to whatever theme hosts
it: the base is the theme's own body size plus an eighth for reading comfort, and the rest of the
scale is relative to that. `--mageos-blog-font` inherits the theme's face for the same reason.
The serif face is kept for post bodies only, as a deliberate editorial choice.

If you restyle the blog, reassign the `--mageos-blog-*` custom properties rather than editing the
rules — `.mageos-blog-related-posts` shrinks the card for product pages purely by reassigning two
of them.

## Reproducing these

Screenshots come from a containerised test rig, on `Magento/luma` with sample content created
through the repository API:

```bash
bin/magento module:enable MageOS_Blog
bin/magento setup:upgrade && bin/magento setup:di:compile
bin/magento config:set mageos_blog/general/enabled 1
bin/magento config:set mageos_blog/related_posts/enabled 1
bin/magento cache:flush
```

There is no seeder yet. The direction for one is sketched in `module-blog-sample-data.md`
alongside the module repositories.
