class PaginatedResult<T> {
  const PaginatedResult({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  bool get hasMore => currentPage < lastPage;

  factory PaginatedResult.singlePage(
    List<T> items, {
    int currentPage = 1,
    int? perPage,
    int? total,
  }) {
    final resolvedPerPage = perPage ?? (items.isEmpty ? 1 : items.length);
    final resolvedTotal = total ?? items.length;
    final resolvedLastPage = resolvedPerPage <= 0
        ? 1
        : ((resolvedTotal + resolvedPerPage - 1) ~/ resolvedPerPage).clamp(
            1,
            999999,
          );
    return PaginatedResult<T>(
      items: items,
      currentPage: currentPage,
      lastPage: resolvedLastPage,
      perPage: resolvedPerPage,
      total: resolvedTotal,
    );
  }
}
